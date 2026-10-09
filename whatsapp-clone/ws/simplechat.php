<?php
declare(strict_types=1);

class simplechat
{
    protected string $host = 'localhost';
    protected string $user = 'root';
    protected string $password = '';
    protected string $dbname = 'whatsapp_clone';
    private mysqli $db;

    public function __construct()
    {
        $this->db = mysqli_connect($this->host, $this->user, $this->password, $this->dbname);
        if (!$this->db) {
            die('Database connection failed. Import database.sql first, then check config.php. Error: ' . e(mysqli_connect_error()));
        }
        mysqli_set_charset($this->db, 'utf8mb4');
    }

    private function query(string $sql, string $types = '', array $params = []): mysqli_stmt
    {
        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) die('Database error: ' . mysqli_error($this->db));
        if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) die('Database error: ' . mysqli_stmt_error($stmt));
        return $stmt;
    }

    public function touchSessionUser(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $id = (int)$_SESSION['user_id'];
            $this->query('UPDATE users SET last_seen = NOW() WHERE id = ?', 'i', [$id]);
        }
    }

    public function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) return null;
        $id = (int)$_SESSION['user_id'];
        $stmt = $this->query('SELECT id,name,phone,password_hash,about,avatar,last_seen,created_at FROM users WHERE id=? LIMIT 1', 'i', [$id]);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $row ?: null;
    }

    public function register(string $name, string $phone, string $password): array
    {
        $name = trim($name); $phone = trim($phone);
        if (mb_strlen($name) < 2) return [false, 'Enter your full name.'];
        if (!preg_match('/^[0-9+()\- ]{7,30}$/', $phone)) return [false, 'Enter a valid phone number.'];
        if (strlen($password) < 6) return [false, 'Password must be at least 6 characters.'];
        $phone = preg_replace('/\s+/', '', $phone);
        $check = $this->query('SELECT id FROM users WHERE phone=? LIMIT 1', 's', [$phone]);
        if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) return [false, 'An account with that phone number already exists.'];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->query('INSERT INTO users (name,phone,password_hash,last_seen) VALUES (?,?,?,NOW())', 'sss', [$name,$phone,$hash]);
        return [true, (string)mysqli_insert_id($this->db)];
    }

    public function login(string $phone, string $password): array
    {
        $phone = preg_replace('/\s+/', '', trim($phone));
        $stmt = $this->query('SELECT id,password_hash FROM users WHERE phone=? LIMIT 1', 's', [$phone]);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row || !password_verify($password, $row['password_hash'])) return [false, 'Incorrect phone number or password.'];
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$row['id'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $id = (int)$row['id'];
        $this->query('UPDATE users SET last_seen=NOW() WHERE id=?', 'i', [$id]);
        return [true, 'ok'];
    }

    public function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $id = (int)$_SESSION['user_id'];
            $this->query('UPDATE users SET last_seen=NOW() WHERE id=?', 'i', [$id]);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function searchUsers(int $me, string $q = ''): array
    {
        $q = trim($q);
        if ($q === '') {
            $stmt = $this->query('SELECT id,name,phone,about,avatar,last_seen FROM users WHERE id<>? ORDER BY name LIMIT 50', 'i', [$me]);
        } else {
            $like = '%' . $q . '%';
            $stmt = $this->query('SELECT id,name,phone,about,avatar,last_seen FROM users WHERE id<>? AND (name LIKE ? OR phone LIKE ?) ORDER BY name LIMIT 50', 'iss', [$me,$like,$like]);
        }
        $out=[]; $res=mysqli_stmt_get_result($stmt); while($r=mysqli_fetch_assoc($res)) $out[]=$r; return $out;
    }

    public function getUser(int $id): ?array
    {
        $stmt=$this->query('SELECT id,name,phone,about,avatar,last_seen FROM users WHERE id=? LIMIT 1','i',[$id]);
        $r=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); return $r?:null;
    }

    public function updateProfile(int $id,string $name,string $about): bool
    {
        $stmt=$this->query('UPDATE users SET name=?,about=? WHERE id=?','ssi',[trim($name),trim($about),$id]);
        return mysqli_stmt_affected_rows($stmt)>=0;
    }

    public function changePassword(int $id,string $old,string $new): array
    {
        $stmt=$this->query('SELECT password_hash FROM users WHERE id=?','i',[$id]);
        $r=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if(!$r || !password_verify($old,$r['password_hash'])) return [false,'Current password is incorrect.'];
        if(strlen($new)<6) return [false,'New password must be at least 6 characters.'];
        $hash=password_hash($new,PASSWORD_DEFAULT);
        $this->query('UPDATE users SET password_hash=? WHERE id=?','si',[$hash,$id]); return [true,'Password changed.'];
    }

    public function saveAvatar(int $id,string $filename): bool
    {
        $stmt=$this->query('UPDATE users SET avatar=? WHERE id=?','si',[$filename,$id]); return mysqli_stmt_affected_rows($stmt)>=0;
    }

    public function getOrCreateConversation(int $a,int $b): int
    {
        $sql='SELECT c.id FROM conversations c JOIN conversation_members cm ON cm.conversation_id=c.id WHERE cm.user_id IN (?,?) GROUP BY c.id HAVING COUNT(DISTINCT cm.user_id)=2 AND (SELECT COUNT(*) FROM conversation_members x WHERE x.conversation_id=c.id)=2 LIMIT 1';
        $stmt=$this->query($sql,'ii',[$a,$b]); $r=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if($r) return (int)$r['id'];
        $this->query('INSERT INTO conversations () VALUES ()'); $cid=(int)mysqli_insert_id($this->db);
        $this->query('INSERT INTO conversation_members (conversation_id,user_id) VALUES (?,?),(?,?)','iiii',[$cid,$a,$cid,$b]); return $cid;
    }

    public function getChats(int $me): array
    {
        $sql="SELECT u.id,u.name,u.phone,u.about,u.avatar,u.last_seen,c.id AS conversation_id,
                (SELECT m.message FROM messages m WHERE m.conversation_id=c.id AND m.deleted_at IS NULL ORDER BY m.id DESC LIMIT 1) AS last_message,
                (SELECT m.created_at FROM messages m WHERE m.conversation_id=c.id AND m.deleted_at IS NULL ORDER BY m.id DESC LIMIT 1) AS last_message_at,
                (SELECT COUNT(*) FROM messages m LEFT JOIN message_reads mr ON mr.message_id=m.id AND mr.user_id=? WHERE m.conversation_id=c.id AND m.sender_id<>? AND mr.message_id IS NULL AND m.deleted_at IS NULL) AS unread_count
              FROM conversation_members cm
              JOIN conversations c ON c.id=cm.conversation_id
              JOIN conversation_members cm2 ON cm2.conversation_id=c.id AND cm2.user_id<>?
              JOIN users u ON u.id=cm2.user_id
              WHERE cm.user_id=?
              ORDER BY COALESCE(last_message_at,c.updated_at) DESC";
        $stmt=$this->query($sql,'iiii',[$me,$me,$me,$me]); $res=mysqli_stmt_get_result($stmt); $out=[]; while($r=mysqli_fetch_assoc($res))$out[]=$r; return $out;
    }

    public function getMessages(int $conversation,int $me,int $limit=100,int $after=0): array
    {
        $sql='SELECT m.id,m.sender_id,m.message,m.message_type,m.attachment,m.created_at,m.edited_at,
              CASE WHEN EXISTS(SELECT 1 FROM message_reads mr WHERE mr.message_id=m.id AND mr.user_id<>m.sender_id) THEN 1 ELSE 0 END AS is_read
              FROM messages m JOIN conversation_members cm ON cm.conversation_id=m.conversation_id AND cm.user_id=?
              WHERE m.conversation_id=? AND m.id>? ORDER BY m.id ASC LIMIT ?';
        $stmt=$this->query($sql,'iiii',[$me,$conversation,$after,$limit]); $res=mysqli_stmt_get_result($stmt); $out=[]; while($r=mysqli_fetch_assoc($res))$out[]=$r; return $out;
    }

    public function sendMessage(int $conversation,int $sender,string $message): ?int
    {
        $message=trim($message); if($message==='') return null;
        $check=$this->query('SELECT 1 FROM conversation_members WHERE conversation_id=? AND user_id=?','ii',[$conversation,$sender]);
        if(mysqli_num_rows(mysqli_stmt_get_result($check))===0) return null;
        $this->query('INSERT INTO messages (conversation_id,sender_id,message) VALUES (?,?,?)','iis',[$conversation,$sender,$message]);
        $id=(int)mysqli_insert_id($this->db); $this->query('UPDATE conversations SET updated_at=NOW() WHERE id=?','i',[$conversation]); return $id;
    }

    public function markRead(int $conversation,int $me): void
    {
        $this->query('INSERT IGNORE INTO message_reads (message_id,user_id) SELECT id,? FROM messages WHERE conversation_id=? AND sender_id<>? AND deleted_at IS NULL','iii',[$me,$conversation,$me]);
    }

    public function deleteMessage(int $message,int $me): bool
    {
        $stmt=$this->query('UPDATE messages SET deleted_at=NOW(),message="This message was deleted." WHERE id=? AND sender_id=?','ii',[$message,$me]); return mysqli_stmt_affected_rows($stmt)>0;
    }

    public function isOnline(?string $lastSeen): bool
    {
        return $lastSeen ? (time()-strtotime($lastSeen) <= 60) : false;
    }
}
