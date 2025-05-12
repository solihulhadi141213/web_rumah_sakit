<?php
    class VisitorLogger {
        private $db;
        
        public function __construct(PDO $dbConnection) {
            $this->db = $dbConnection;
        }
        
        public function logVisit() {
            $data = [
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'request_url' => $_SERVER['REQUEST_URI'],
                'http_status' => http_response_code(),
                'referrer_url' => $_SERVER['HTTP_REFERER'] ?? '',
                'session_id' => session_id(),
                'query_string' => !empty($_GET) ? json_encode($_GET) : null,
                'post_data' => !empty($_POST) ? json_encode($_POST) : null,
                'headers' => json_encode(getallheaders()),
                'user_id' => $_SESSION['user_id'] ?? null
            ];
            
            $sql = "INSERT INTO visitor_logs (
                ip_address, user_agent, request_method, request_url, 
                http_status, referrer_url, session_id, query_string, 
                post_data, headers, user_id
            ) VALUES (
                :ip_address, :user_agent, :request_method, :request_url, 
                :http_status, :referrer_url, :session_id, :query_string, 
                :post_data, :headers, :user_id
            )";
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($data);
            } catch (PDOException $e) {
                // Log error ke file jika database error
                error_log("Failed to log visitor: " . $e->getMessage());
            }
        }
        
        // Fungsi untuk mendeteksi aktivitas mencurigakan
        public function detectSuspiciousActivity($ip) {
            $sql = "SELECT COUNT(*) as count FROM visitor_logs 
                    WHERE ip_address = ? AND timestamp > NOW() - INTERVAL 1 HOUR";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ip]);
            $result = $stmt->fetch();
            
            return ($result['count'] > 100); // Jika > 100 request/jam dianggap mencurigakan
        }
    }
?>