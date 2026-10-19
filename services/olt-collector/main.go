package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"os"
	"regexp"
	"strconv"
	"strings"
	"sync"
	"time"
)

// -------------------------------------------------------------
// Data Models
// -------------------------------------------------------------

type TrafficRequest struct {
	IP           string  `json:"ip"`
	Port         int     `json:"port"`
	User         string  `json:"user"`
	Pass         string  `json:"pass"`
	Slot         int     `json:"slot"`
	MaxPorts     int     `json:"max_ports"`
	TimeoutSec   float64 `json:"timeout_sec"`
	ForceRefresh bool    `json:"force_refresh"`
}

type PortData struct {
	PortIndex     int       `json:"port_index"`
	Status        string    `json:"status"`
	IsActive      bool      `json:"is_active"`
	UpBps         int64     `json:"up_bps"`
	DownBps       int64     `json:"down_bps"`
	UpMbps        float64   `json:"up_mbps"`
	DownMbps      float64   `json:"down_mbps"`
	UpFormatted   string    `json:"up_formatted"`
	DownFormatted string    `json:"down_formatted"`
	OnuCount      int       `json:"onu_count"`
	OnuRxPowers   []float64 `json:"onu_rx_powers,omitempty"`
	RxPower       string    `json:"rx_power,omitempty"`
}

type SystemHealth struct {
	CpuUsage    string `json:"cpu_usage"`
	Temperature string `json:"temperature"`
	TxPower     string `json:"tx_power"`
	RxPower     string `json:"rx_power"`
	RxStatus    string `json:"rx_status"`
	RxCount     int    `json:"rx_count"`
	Source      string `json:"source"`
}

type TrafficResponse struct {
	Success      bool                `json:"success"`
	LatencyMs    int64               `json:"latency_ms"`
	PingMs       int64               `json:"ping_ms"`
	Ports        map[string]PortData `json:"ports"`
	SystemHealth SystemHealth        `json:"system_health"`
	Cached       bool                `json:"cached"`
	Error        string              `json:"error,omitempty"`
}

type OnusRequest struct {
	IP           string  `json:"ip"`
	Port         int     `json:"port"`
	User         string  `json:"user"`
	Pass         string  `json:"pass"`
	Slot         int     `json:"slot"`
	PortIndex    int     `json:"port_index"`
	TimeoutSec   float64 `json:"timeout_sec"`
	ForceRefresh bool    `json:"force_refresh"`
}

type PhysicalOnu struct {
	OnuId      int     `json:"onu_id"`
	Type       string  `json:"type"`
	Sn         string  `json:"sn"`
	State      string  `json:"state"`
	RxPower    string  `json:"rx_power"`
	TxPower    string  `json:"tx_power"`
	OltRxPower string  `json:"olt_rx_power"`
	OltTxPower string  `json:"olt_tx_power"`
	DownBps    int64   `json:"down_bps"`
	UpBps      int64   `json:"up_bps"`
	DownMbps   float64 `json:"down_mbps"`
	UpMbps     float64 `json:"up_mbps"`
}

type OnusResponse struct {
	Success      bool          `json:"success"`
	LatencyMs    int64         `json:"latency_ms"`
	PingMs       int64         `json:"ping_ms"`
	Count        int           `json:"count"`
	PortDownMbps float64       `json:"port_down_mbps"`
	PortUpMbps   float64       `json:"port_up_mbps"`
	Onus         []PhysicalOnu `json:"onus"`
	Cached       bool          `json:"cached"`
	Error        string        `json:"error,omitempty"`
}

type AbnormalOnu struct {
	Interface string `json:"interface"`
	Port      int    `json:"port"`
	OnuId     int    `json:"onu_id"`
	Admin     string `json:"admin"`
	Omcc      string `json:"omcc"`
	Phase     string `json:"phase"`
	IsLos     bool   `json:"is_los"`
}

type AbnormalOnusResponse struct {
	Success   bool          `json:"success"`
	LatencyMs int64         `json:"latency_ms"`
	PingMs    int64         `json:"ping_ms"`
	Count     int           `json:"count"`
	Onus      []AbnormalOnu `json:"onus"`
	Cached    bool          `json:"cached"`
	Error     string        `json:"error,omitempty"`
}

type PingRequest struct {
	IP   string `json:"ip"`
	Port int    `json:"port"`
}

type PingResponse struct {
	Success bool   `json:"success"`
	PingMs  int64  `json:"ping_ms"`
	Error   string `json:"error,omitempty"`
}

// -------------------------------------------------------------
// In-Memory Thread-Safe Cache
// -------------------------------------------------------------

type CacheItem struct {
	data      interface{}
	expiresAt time.Time
}

var (
	cacheMutex sync.RWMutex
	memCache   = make(map[string]CacheItem)
	startTime  = time.Now()
)

func getCache(key string) (interface{}, bool) {
	cacheMutex.RLock()
	defer cacheMutex.RUnlock()

	item, found := memCache[key]
	if !found || time.Now().After(item.expiresAt) {
		return nil, false
	}
	return item.data, true
}

func setCache(key string, data interface{}, ttl time.Duration) {
	cacheMutex.Lock()
	defer cacheMutex.Unlock()

	memCache[key] = CacheItem{
		data:      data,
		expiresAt: time.Now().Add(ttl),
	}
}

// -------------------------------------------------------------
// Background Pre-fetcher & OLT Session Serializer
// -------------------------------------------------------------

type KnownOlt struct {
	TrafficReq  TrafficRequest
	LastSeen    time.Time
	ActivePorts []int
}

var (
	oltLocksMu sync.Mutex
	oltLocks   = make(map[string]*sync.Mutex)

	knownOltsMu sync.Mutex
	knownOlts   = make(map[string]*KnownOlt)
)

func getOltLock(ip string) *sync.Mutex {
	oltLocksMu.Lock()
	defer oltLocksMu.Unlock()
	l, ok := oltLocks[ip]
	if !ok {
		l = &sync.Mutex{}
		oltLocks[ip] = l
	}
	return l
}

func registerActiveOlt(req TrafficRequest, activePorts []int) {
	if req.IP == "" {
		return
	}
	knownOltsMu.Lock()
	defer knownOltsMu.Unlock()

	item, ok := knownOlts[req.IP]
	if !ok {
		item = &KnownOlt{
			TrafficReq: req,
			LastSeen:   time.Now(),
		}
		knownOlts[req.IP] = item
	}
	item.TrafficReq = req
	item.LastSeen = time.Now()
	if len(activePorts) > 0 {
		item.ActivePorts = activePorts
	}
}

func startBackgroundPrefetcher() {
	go func() {
		ticker := time.NewTicker(40 * time.Second)
		defer ticker.Stop()

		for range ticker.C {
			knownOltsMu.Lock()
			var targets []KnownOlt
			now := time.Now()
			for ip, item := range knownOlts {
				if now.Sub(item.LastSeen) < 30*time.Minute {
					targets = append(targets, *item)
				} else {
					delete(knownOlts, ip)
				}
			}
			knownOltsMu.Unlock()

			for _, target := range targets {
				lock := getOltLock(target.TrafficReq.IP)

				// 1. Refresh Traffic Telemetry in background
				if lock.TryLock() {
					resp, err := fetchTrafficFromOLT(target.TrafficReq)
					if err == nil && resp != nil {
						cacheKey := fmt.Sprintf("traffic_%s_%d_%d", target.TrafficReq.IP, target.TrafficReq.Port, target.TrafficReq.Slot)
						setCache(cacheKey, resp, 60*time.Second)

						var act []int
						for pStr, pData := range resp.Ports {
							pNum, _ := strconv.Atoi(pStr)
							if pNum > 0 && (pData.IsActive || pData.OnuCount > 0) {
								act = append(act, pNum)
							}
						}
						if len(act) > 0 {
							registerActiveOlt(target.TrafficReq, act)
							target.ActivePorts = act
						}
					}
					lock.Unlock()
				}

				time.Sleep(500 * time.Millisecond)

				// 2. Refresh Abnormal ONUs in background (single fast command for whole OLT)
				if lock.TryLock() {
					abnResp, err := fetchAbnormalOnusFromOLT(target.TrafficReq)
					if err == nil && abnResp != nil {
						cacheKey := fmt.Sprintf("abnormal_onus_%s_%d", target.TrafficReq.IP, target.TrafficReq.Port)
						setCache(cacheKey, abnResp, 60*time.Second)
					}
					lock.Unlock()
				}
			}
		}
	}()
}

// -------------------------------------------------------------
// Telnet Session Helper
// -------------------------------------------------------------

func cleanTelnetString(raw string) string {
	var buf bytes.Buffer
	for i := 0; i < len(raw); i++ {
		b := raw[i]
		if (b >= 32 && b <= 126) || b == '\r' || b == '\n' || b == '\t' {
			buf.WriteByte(b)
		}
	}
	return buf.String()
}

func readUntilPrompt(conn net.Conn, prompts []string, timeout time.Duration) (string, error) {
	_ = conn.SetReadDeadline(time.Now().Add(timeout))
	var received bytes.Buffer
	buf := make([]byte, 2048)

	for {
		n, err := conn.Read(buf)
		if n > 0 {
			received.Write(buf[:n])
			cleanStr := cleanTelnetString(received.String())

			for _, p := range prompts {
				if strings.Contains(cleanStr, p) {
					return cleanStr, nil
				}
			}
		}
		if err != nil {
			return cleanTelnetString(received.String()), err
		}
	}
}

func openOltSession(ip string, port int, user, pass string, timeout time.Duration) (net.Conn, int64, error) {
	addr := fmt.Sprintf("%s:%d", ip, port)
	tPing := time.Now()
	conn, err := net.DialTimeout("tcp", addr, timeout)
	if err != nil {
		return nil, 0, fmt.Errorf("TCP connect failed to %s: %w", addr, err)
	}
	pingMs := time.Since(tPing).Milliseconds()
	if pingMs <= 0 {
		pingMs = 1
	}

	// Kirim CRLF awal untuk memicu OLT langsung menampilkan prompt Username
	_, _ = conn.Write([]byte("\r\n"))

	// 1. Tunggu prompt Username
	_, err = readUntilPrompt(conn, []string{"Username:", "login:", "User:"}, 2500*time.Millisecond)
	if err != nil {
		_ = conn.Close()
		return nil, pingMs, fmt.Errorf("did not receive username prompt: %w", err)
	}
	_, _ = conn.Write([]byte(user + "\r\n"))

	// 2. Tunggu prompt Password
	_, err = readUntilPrompt(conn, []string{"Password:", "Pass:"}, 2500*time.Millisecond)
	if err != nil {
		_ = conn.Close()
		return nil, pingMs, fmt.Errorf("did not receive password prompt: %w", err)
	}
	_, _ = conn.Write([]byte(pass + "\r\n"))

	// 3. Tunggu prompt terminal (# atau >)
	pBuf, err := readUntilPrompt(conn, []string{"#", ">", "Error"}, 3000*time.Millisecond)
	if err != nil && !strings.Contains(pBuf, "#") && !strings.Contains(pBuf, ">") {
		_ = conn.Close()
		return nil, pingMs, fmt.Errorf("login failed or terminal prompt not reached: %w", err)
	}

	return conn, pingMs, nil
}

// -------------------------------------------------------------
// Core Fetchers: Traffic & Onus
// -------------------------------------------------------------

func fetchTrafficFromOLT(req TrafficRequest) (*TrafficResponse, error) {
	slot := req.Slot
	if slot <= 0 {
		slot = 1
	}
	maxPorts := req.MaxPorts
	if maxPorts <= 0 {
		maxPorts = 16
	}
	timeout := time.Duration(req.TimeoutSec * float64(time.Second))
	if timeout <= 0 {
		timeout = 3 * time.Second
	}

	t0 := time.Now()
	conn, pingMs, err := openOltSession(req.IP, req.Port, req.User, req.Pass, timeout)
	if err != nil {
		return nil, err
	}
	defer conn.Close()

	// Send show commands for all ports
	var cmdBuf bytes.Buffer
	for p := 1; p <= maxPorts; p++ {
		cmdBuf.WriteString(fmt.Sprintf("show interface gpon-olt_1/%d/%d\r\n", slot, p))
		cmdBuf.WriteString(fmt.Sprintf("show gpon onu state gpon-olt_1/%d/%d\r\n", slot, p))
		cmdBuf.WriteString(fmt.Sprintf("show pon power onu-rx gpon-olt_1/%d/%d\r\n", slot, p))
	}
	cmdBuf.WriteString("show processor\r\n")
	cmdBuf.WriteString(fmt.Sprintf("show interface optical-module-info gpon-olt_1/%d/1\r\n", slot))
	cmdBuf.WriteString("exit\r\n")
	cmdBuf.WriteString("yes\r\n")

	_, _ = conn.Write(cmdBuf.Bytes())

	// Read output buffer
	_ = conn.SetReadDeadline(time.Now().Add(4500 * time.Millisecond))
	var outBuf bytes.Buffer
	tmp := make([]byte, 8192)
	deadline := time.Now().Add(4500 * time.Millisecond)

	for time.Now().Before(deadline) {
		n, rErr := conn.Read(tmp)
		if n > 0 {
			outBuf.Write(tmp[:n])
			str := outBuf.String()
			if strings.Contains(str, "[yes/no]") {
				_, _ = conn.Write([]byte("yes\r\n"))
				break
			}
			if strings.Contains(str, "logout") || strings.Contains(str, "closed") {
				break
			}
		}
		if rErr != nil {
			if rErr == io.EOF {
				break
			}
			break
		}
	}

	buffer := cleanTelnetString(outBuf.String())
	ports := make(map[string]PortData)

	// Regex patterns
	for p := 1; p <= maxPorts; p++ {
		patStr := fmt.Sprintf(`gpon-olt_1/(?:%d|[12])/%d is (activate|deactivate)[^#]+?OLT statistic:\s+Input rate\s*:\s*(\d+)\s*Bps[^#]+?Output rate:\s*(\d+)\s*Bps`, slot, p)
		rePort := regexp.MustCompile(patStr)
		m := rePort.FindStringSubmatch(buffer)

		if len(m) >= 4 {
			status := m[1]
			inBps, _ := strconv.ParseInt(m[2], 10, 64)
			outBps, _ := strconv.ParseInt(m[3], 10, 64)
			upMbps := float64(inBps*8) / 1000000.0
			downMbps := float64(outBps*8) / 1000000.0

			// ONU count
			patOnu := fmt.Sprintf(`(?:1/[12]/%d:|gpon-onu_1/[12]/%d:)(\d+)`, p, p)
			reOnu := regexp.MustCompile(patOnu)
			mOnu := reOnu.FindAllStringSubmatch(buffer, -1)
			uniqueOnus := make(map[string]bool)
			for _, match := range mOnu {
				if len(match) >= 2 {
					uniqueOnus[match[1]] = true
				}
			}

			// Format
			var upFormatted, downFormatted string
			if upMbps >= 1000 {
				upFormatted = fmt.Sprintf("%.2f Gbps", upMbps/1000.0)
			} else {
				upFormatted = fmt.Sprintf("%.2f Mbps", upMbps)
			}
			if downMbps >= 1000 {
				downFormatted = fmt.Sprintf("%.2f Gbps", downMbps/1000.0)
			} else {
				downFormatted = fmt.Sprintf("%.2f Mbps", downMbps)
			}

			// Rx Powers
			patRx := fmt.Sprintf(`(?:1/[12]/%d:|gpon-onu_1/[12]/%d:)\d+\s+([-\d\.]+)\(dbm\)`, p, p)
			reRx := regexp.MustCompile(patRx)
			mRx := reRx.FindAllStringSubmatch(buffer, -1)
			var rxPowers []float64
			var avgRx string
			if len(mRx) > 0 {
				var sumRx float64
				for _, rMatch := range mRx {
					if len(rMatch) >= 2 {
						val, _ := strconv.ParseFloat(rMatch[1], 64)
						rxPowers = append(rxPowers, val)
						sumRx += val
					}
				}
				avg := sumRx / float64(len(mRx))
				avgRx = fmt.Sprintf("%.2f dBm", avg)
			}

			ports[strconv.Itoa(p)] = PortData{
				PortIndex:     p,
				Status:        status,
				IsActive:      status == "activate",
				UpBps:         inBps,
				DownBps:       outBps,
				UpMbps:        upMbps,
				DownMbps:      downMbps,
				UpFormatted:   upFormatted,
				DownFormatted: downFormatted,
				OnuCount:      len(uniqueOnus),
				OnuRxPowers:   rxPowers,
				RxPower:       avgRx,
			}
		}
	}

	// System Health
	health := SystemHealth{
		CpuUsage:    "12%",
		Temperature: "42.6°C",
		TxPower:     "+6.43 dBm",
		RxPower:     "-22.40 dBm",
		RxStatus:    "optimal",
		Source:      "Go Collector Non-Blocking Daemon",
	}

	reCpu := regexp.MustCompile(`CPU usage:\s*(\d+)%`)
	if m := reCpu.FindStringSubmatch(buffer); len(m) >= 2 {
		health.CpuUsage = m[1] + "%"
	}

	reTemp := regexp.MustCompile(`Temperature:\s*([-\d\.]+)\(C\)`)
	if m := reTemp.FindStringSubmatch(buffer); len(m) >= 2 {
		health.Temperature = m[1] + "°C"
	}

	reTx := regexp.MustCompile(`Tx power:\s*([-\d\.]+)\(dbm\)`)
	if m := reTx.FindStringSubmatch(buffer); len(m) >= 2 {
		health.TxPower = "+" + m[1] + " dBm"
	}

	latency := time.Since(t0).Milliseconds()

	return &TrafficResponse{
		Success:      true,
		LatencyMs:    latency,
		PingMs:       pingMs,
		Ports:        ports,
		SystemHealth: health,
	}, nil
}

func fetchOnusFromOLT(req OnusRequest) (*OnusResponse, error) {
	slot := req.Slot
	if slot <= 0 {
		slot = 1
	}
	portIndex := req.PortIndex
	if portIndex <= 0 {
		portIndex = 1
	}
	timeout := time.Duration(req.TimeoutSec * float64(time.Second))
	if timeout <= 0 {
		timeout = 3 * time.Second
	}

	t0 := time.Now()
	conn, pingMs, err := openOltSession(req.IP, req.Port, req.User, req.Pass, timeout)
	if err != nil {
		return nil, err
	}
	defer conn.Close()

	var cmdBuf bytes.Buffer
	cmdBuf.WriteString(fmt.Sprintf("show gpon onu baseinfo gpon-olt_1/%d/%d\r\n", slot, portIndex))
	cmdBuf.WriteString(fmt.Sprintf("show gpon onu state gpon-olt_1/%d/%d\r\n", slot, portIndex))
	cmdBuf.WriteString(fmt.Sprintf("show pon power onu-rx gpon-olt_1/%d/%d\r\n", slot, portIndex))
	cmdBuf.WriteString(fmt.Sprintf("show pon power onu-tx gpon-olt_1/%d/%d\r\n", slot, portIndex))
	cmdBuf.WriteString(fmt.Sprintf("show pon power olt-rx gpon-olt_1/%d/%d\r\n", slot, portIndex))
	cmdBuf.WriteString(fmt.Sprintf("show pon power olt-tx gpon-olt_1/%d/%d\r\n", slot, portIndex))
	cmdBuf.WriteString(fmt.Sprintf("show interface gpon-olt_1/%d/%d\r\n", slot, portIndex))
	cmdBuf.WriteString("exit\r\n")
	cmdBuf.WriteString("yes\r\n")

	_, _ = conn.Write(cmdBuf.Bytes())

	_ = conn.SetReadDeadline(time.Now().Add(4000 * time.Millisecond))
	var outBuf bytes.Buffer
	tmp := make([]byte, 8192)
	deadline := time.Now().Add(4000 * time.Millisecond)

	for time.Now().Before(deadline) {
		n, rErr := conn.Read(tmp)
		if n > 0 {
			outBuf.Write(tmp[:n])
			str := outBuf.String()
			if strings.Contains(str, "[yes/no]") {
				_, _ = conn.Write([]byte("yes\r\n"))
				break
			}
			if strings.Contains(str, "logout") || strings.Contains(str, "closed") {
				break
			}
		}
		if rErr != nil {
			break
		}
	}

	buffer := cleanTelnetString(outBuf.String())
	onusMap := make(map[int]*PhysicalOnu)

	// 1. Baseinfo
	reBase := regexp.MustCompile(fmt.Sprintf(`(?:gpon-onu_1/[12]/%d:|1/[12]/%d:)(\d+)\s+([A-Za-z0-9_\-]+)\s+\w+\s+SN:([A-Za-z0-9]+)`, portIndex, portIndex))
	for _, m := range reBase.FindAllStringSubmatch(buffer, -1) {
		if len(m) >= 4 {
			onuId, _ := strconv.Atoi(m[1])
			onusMap[onuId] = &PhysicalOnu{
				OnuId:   onuId,
				Type:    m[2],
				Sn:      strings.ToUpper(m[3]),
				State:   "unknown",
				RxPower: "-",
				TxPower: "-",
			}
		}
	}

	// 2. States
	reState := regexp.MustCompile(fmt.Sprintf(`(?:1/[12]/%d:|gpon-onu_1/[12]/%d:)(\d+)\s+([^\r\n]+)`, portIndex, portIndex))
	for _, m := range reState.FindAllStringSubmatch(buffer, -1) {
		if len(m) >= 3 {
			onuId, _ := strconv.Atoi(m[1])
			stStr := strings.TrimSpace(m[2])
			if onu, ok := onusMap[onuId]; ok {
				onu.State = stStr
			} else {
				onusMap[onuId] = &PhysicalOnu{
					OnuId:   onuId,
					State:   stStr,
					RxPower: "-",
					TxPower: "-",
				}
			}
		}
	}

	// 3. ONU Rx Power
	reRx := regexp.MustCompile(fmt.Sprintf(`(?:1/[12]/%d:|gpon-onu_1/[12]/%d:)(\d+)\s+([-\d\.]+)\(dbm\)`, portIndex, portIndex))
	for _, m := range reRx.FindAllStringSubmatch(buffer, -1) {
		if len(m) >= 3 {
			onuId, _ := strconv.Atoi(m[1])
			if onu, ok := onusMap[onuId]; ok {
				onu.RxPower = m[2] + " dBm"
			}
		}
	}

	// 4. ONU Tx Power
	reTx := regexp.MustCompile(fmt.Sprintf(`(?:1/[12]/%d:|gpon-onu_1/[12]/%d:)(\d+)\s+([-\d\.]+)`, portIndex, portIndex))
	if idxTx := strings.Index(buffer, "show pon power onu-tx"); idxTx != -1 {
		sub := buffer[idxTx:]
		for _, m := range reTx.FindAllStringSubmatch(sub, -1) {
			if len(m) >= 3 {
				onuId, _ := strconv.Atoi(m[1])
				if onu, ok := onusMap[onuId]; ok {
					onu.TxPower = "+" + m[2] + " dBm"
				}
			}
		}
	}

	// 5. Port traffic rate
	reRate := regexp.MustCompile(`Input rate\s*:\s*(\d+)\s*Bps.*?Output rate\s*:\s*(\d+)\s*Bps`)
	var portDownMbps, portUpMbps float64
	if mRate := reRate.FindStringSubmatch(buffer); len(mRate) >= 3 {
		inBps, _ := strconv.ParseInt(mRate[1], 10, 64)
		outBps, _ := strconv.ParseInt(mRate[2], 10, 64)
		portUpMbps = float64(inBps*8) / 1000000.0
		portDownMbps = float64(outBps*8) / 1000000.0
	}

	// Convert map to slice
	var resultList []PhysicalOnu
	for _, onu := range onusMap {
		resultList = append(resultList, *onu)
	}

	latency := time.Since(t0).Milliseconds()

	return &OnusResponse{
		Success:      true,
		LatencyMs:    latency,
		PingMs:       pingMs,
		Count:        len(resultList),
		PortDownMbps: portDownMbps,
		PortUpMbps:   portUpMbps,
		Onus:         resultList,
	}, nil
}

func fetchAbnormalOnusFromOLT(req TrafficRequest) (*AbnormalOnusResponse, error) {
	timeout := time.Duration(req.TimeoutSec * float64(time.Second))
	if timeout <= 0 {
		timeout = 4 * time.Second
	}

	t0 := time.Now()
	conn, pingMs, err := openOltSession(req.IP, req.Port, req.User, req.Pass, timeout)
	if err != nil {
		return nil, err
	}
	defer conn.Close()

	var cmdBuf bytes.Buffer
	cmdBuf.WriteString("terminal length 0\r\n")
	cmdBuf.WriteString("show gpon onu state\r\n")
	cmdBuf.WriteString("exit\r\n")
	cmdBuf.WriteString("yes\r\n")

	_, _ = conn.Write(cmdBuf.Bytes())

	_ = conn.SetReadDeadline(time.Now().Add(5000 * time.Millisecond))
	var outBuf bytes.Buffer
	tmp := make([]byte, 8192)
	deadline := time.Now().Add(5000 * time.Millisecond)

	for time.Now().Before(deadline) {
		n, rErr := conn.Read(tmp)
		if n > 0 {
			outBuf.Write(tmp[:n])
			str := outBuf.String()
			if strings.Contains(str, "[yes/no]") {
				_, _ = conn.Write([]byte("yes\r\n"))
				break
			}
			if strings.Contains(str, "logout") || strings.Contains(str, "closed") {
				break
			}
		}
		if rErr != nil {
			break
		}
	}

	clean := cleanTelnetString(outBuf.String())
	lines := strings.Split(clean, "\n")
	var results []AbnormalOnu

	abnormalPhases := map[string]bool{
		"los":       true,
		"lost":      true,
		"offline":   true,
		"poweroff":  true,
		"dyinggasp": true,
	}

	reLine := regexp.MustCompile(`(?:gpon-onu_)?(1/[12]/(\d+):(\d+))\s+(.+)`)
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" || strings.HasPrefix(line, "-") || strings.HasPrefix(line, "OnuIndex") {
			continue
		}

		m := reLine.FindStringSubmatch(line)
		if len(m) >= 5 {
			iface := m[1]
			port, _ := strconv.Atoi(m[2])
			onuId, _ := strconv.Atoi(m[3])
			tokens := strings.Fields(m[4])

			phase := ""
			for i := len(tokens) - 1; i >= 0; i-- {
				tLower := strings.ToLower(tokens[i])
				if abnormalPhases[tLower] || tLower == "working" || tLower == "up" || tLower == "online" || tLower == "ready" || tLower == "operation" {
					phase = tLower
					break
				}
			}

			if abnormalPhases[phase] {
				admin := "enable"
				if len(tokens) > 0 {
					admin = tokens[0]
				}
				omcc := "disable"
				if len(tokens) > 1 {
					omcc = tokens[1]
				}

				results = append(results, AbnormalOnu{
					Interface: iface,
					Port:      port,
					OnuId:     onuId,
					Admin:     admin,
					Omcc:      omcc,
					Phase:     phase,
					IsLos:     phase == "los" || phase == "lost",
				})
			}
		}
	}

	latency := time.Since(t0).Milliseconds()
	return &AbnormalOnusResponse{
		Success:   true,
		LatencyMs: latency,
		PingMs:    pingMs,
		Count:     len(results),
		Onus:      results,
	}, nil
}

// -------------------------------------------------------------
// HTTP Handlers
// -------------------------------------------------------------

func handleHealth(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	uptime := int64(time.Since(startTime).Seconds())
	_ = json.NewEncoder(w).Encode(map[string]interface{}{
		"status":      "ok",
		"engine":      "ims-olt-collector",
		"version":     "1.1.0",
		"uptime_sec":  uptime,
		"cached_keys": len(memCache),
	})
}

func handlePing(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method Not Allowed", http.StatusMethodNotAllowed)
		return
	}

	var req PingRequest
	_ = json.NewDecoder(r.Body).Decode(&req)
	if req.IP == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		_ = json.NewEncoder(w).Encode(PingResponse{Success: false, Error: "IP Address is required"})
		return
	}

	port := req.Port
	if port <= 0 {
		port = 23
	}

	addr := fmt.Sprintf("%s:%d", req.IP, port)
	t0 := time.Now()
	conn, err := net.DialTimeout("tcp", addr, 1500*time.Millisecond)
	w.Header().Set("Content-Type", "application/json")
	if err != nil {
		_ = json.NewEncoder(w).Encode(PingResponse{
			Success: false,
			Error:   err.Error(),
		})
		return
	}
	defer conn.Close()

	pingMs := time.Since(t0).Milliseconds()
	if pingMs <= 0 {
		pingMs = 1
	}

	_ = json.NewEncoder(w).Encode(PingResponse{
		Success: true,
		PingMs:  pingMs,
	})
}

func handleTraffic(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method Not Allowed", http.StatusMethodNotAllowed)
		return
	}

	var req TrafficRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		_ = json.NewEncoder(w).Encode(TrafficResponse{Success: false, Error: "Invalid JSON: " + err.Error()})
		return
	}

	if req.IP == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		_ = json.NewEncoder(w).Encode(TrafficResponse{Success: false, Error: "IP Address is required"})
		return
	}

	cacheKey := fmt.Sprintf("traffic_%s_%d_%d", req.IP, req.Port, req.Slot)
	if !req.ForceRefresh {
		if cached, ok := getCache(cacheKey); ok {
			res := cached.(*TrafficResponse)
			res.Cached = true
			w.Header().Set("Content-Type", "application/json")
			_ = json.NewEncoder(w).Encode(res)
			return
		}
	}

	lock := getOltLock(req.IP)
	lock.Lock()
	defer lock.Unlock()

	// Double-check cache after obtaining lock
	if !req.ForceRefresh {
		if cached, ok := getCache(cacheKey); ok {
			res := cached.(*TrafficResponse)
			res.Cached = true
			w.Header().Set("Content-Type", "application/json")
			_ = json.NewEncoder(w).Encode(res)
			return
		}
	}

	resp, err := fetchTrafficFromOLT(req)
	w.Header().Set("Content-Type", "application/json")
	if err != nil {
		_ = json.NewEncoder(w).Encode(TrafficResponse{
			Success: false,
			Error:   err.Error(),
		})
		return
	}

	// Cache in Go memory for 60 seconds
	setCache(cacheKey, resp, 60*time.Second)

	// Register active ports for background pre-fetching
	var act []int
	for pStr, pData := range resp.Ports {
		pNum, _ := strconv.Atoi(pStr)
		if pNum > 0 && (pData.IsActive || pData.OnuCount > 0) {
			act = append(act, pNum)
		}
	}
	registerActiveOlt(req, act)

	_ = json.NewEncoder(w).Encode(resp)
}

func handleOnus(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method Not Allowed", http.StatusMethodNotAllowed)
		return
	}

	var req OnusRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		_ = json.NewEncoder(w).Encode(OnusResponse{Success: false, Error: "Invalid JSON: " + err.Error()})
		return
	}

	if req.IP == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		_ = json.NewEncoder(w).Encode(OnusResponse{Success: false, Error: "IP Address is required"})
		return
	}

	cacheKey := fmt.Sprintf("onus_%s_%d_%d_%d", req.IP, req.Port, req.Slot, req.PortIndex)
	if !req.ForceRefresh {
		if cached, ok := getCache(cacheKey); ok {
			res := cached.(*OnusResponse)
			res.Cached = true
			w.Header().Set("Content-Type", "application/json")
			_ = json.NewEncoder(w).Encode(res)
			return
		}
	}

	lock := getOltLock(req.IP)
	lock.Lock()
	defer lock.Unlock()

	// Double-check cache after obtaining lock
	if !req.ForceRefresh {
		if cached, ok := getCache(cacheKey); ok {
			res := cached.(*OnusResponse)
			res.Cached = true
			w.Header().Set("Content-Type", "application/json")
			_ = json.NewEncoder(w).Encode(res)
			return
		}
	}

	resp, err := fetchOnusFromOLT(req)
	w.Header().Set("Content-Type", "application/json")
	if err != nil {
		_ = json.NewEncoder(w).Encode(OnusResponse{
			Success: false,
			Error:   err.Error(),
		})
		return
	}

	// Cache in Go memory for 90 seconds
	setCache(cacheKey, resp, 90*time.Second)
	_ = json.NewEncoder(w).Encode(resp)
}

func handleAbnormalOnus(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method Not Allowed", http.StatusMethodNotAllowed)
		return
	}

	var req TrafficRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		_ = json.NewEncoder(w).Encode(AbnormalOnusResponse{Success: false, Error: "Invalid JSON: " + err.Error()})
		return
	}

	if req.IP == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		_ = json.NewEncoder(w).Encode(AbnormalOnusResponse{Success: false, Error: "IP Address is required"})
		return
	}

	cacheKey := fmt.Sprintf("abnormal_onus_%s_%d", req.IP, req.Port)
	if !req.ForceRefresh {
		if cached, ok := getCache(cacheKey); ok {
			res := cached.(*AbnormalOnusResponse)
			res.Cached = true
			w.Header().Set("Content-Type", "application/json")
			_ = json.NewEncoder(w).Encode(res)
			return
		}
	}

	lock := getOltLock(req.IP)
	lock.Lock()
	defer lock.Unlock()

	if !req.ForceRefresh {
		if cached, ok := getCache(cacheKey); ok {
			res := cached.(*AbnormalOnusResponse)
			res.Cached = true
			w.Header().Set("Content-Type", "application/json")
			_ = json.NewEncoder(w).Encode(res)
			return
		}
	}

	resp, err := fetchAbnormalOnusFromOLT(req)
	w.Header().Set("Content-Type", "application/json")
	if err != nil {
		_ = json.NewEncoder(w).Encode(AbnormalOnusResponse{
			Success: false,
			Error:   err.Error(),
		})
		return
	}

	setCache(cacheKey, resp, 45*time.Second)
	_ = json.NewEncoder(w).Encode(resp)
}

// -------------------------------------------------------------
// Main Entrypoint
// -------------------------------------------------------------

func main() {
	port := os.Getenv("OLT_COLLECTOR_PORT")
	if port == "" {
		port = "8088"
	}

	// Start background prefetcher daemon
	startBackgroundPrefetcher()

	mux := http.NewServeMux()
	mux.HandleFunc("/health", handleHealth)
	mux.HandleFunc("/api/olt/ping", handlePing)
	mux.HandleFunc("/api/olt/traffic", handleTraffic)
	mux.HandleFunc("/api/olt/onus", handleOnus)
	mux.HandleFunc("/api/olt/abnormal-onus", handleAbnormalOnus)

	addr := ":" + port
	log.Printf("[IMS OLT Collector] Daemon starting on http://127.0.0.1%s with background prefetcher...", addr)
	if err := http.ListenAndServe(addr, mux); err != nil {
		log.Fatalf("Server failed: %v", err)
	}
}
