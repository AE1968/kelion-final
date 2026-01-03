<?php
function db(): SQLite3
{
  static $db = null;
  global $CONFIG;
  if ($db)
    return $db;

  $path = $CONFIG['db']['sqlite_path'];
  $dir = dirname($path);
  if (!is_dir($dir))
    @mkdir($dir, 0775, true);

  $db = new SQLite3($path);
  $db->exec('PRAGMA journal_mode=WAL;');
  $db->exec('PRAGMA foreign_keys=ON;');
  return $db;
}

function db_init(): void
{
  $db = db();

  $db->exec('
    CREATE TABLE IF NOT EXISTS users(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT UNIQUE NOT NULL,
      email TEXT UNIQUE,
      phone TEXT,
      passhash TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT "user",
      status TEXT NOT NULL DEFAULT "active",
      email_verified INTEGER NOT NULL DEFAULT 0,
      age_confirmed INTEGER NOT NULL DEFAULT 0,
      phone_verified INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      last_login_at TEXT
    );
  ');

  $db->exec('
    CREATE TABLE IF NOT EXISTS plans(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      duration_days INTEGER NOT NULL,
      price_minor INTEGER NOT NULL,
      currency TEXT NOT NULL DEFAULT "GBP",
      active INTEGER NOT NULL DEFAULT 1,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
  ');

  $db->exec('
    CREATE TABLE IF NOT EXISTS subscriptions(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      plan_id INTEGER NOT NULL,
      status TEXT NOT NULL,
      starts_at TEXT,
      ends_at TEXT,
      auto_renew INTEGER NOT NULL DEFAULT 0,
      reconnected_from INTEGER,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY(plan_id) REFERENCES plans(id),
      FOREIGN KEY(reconnected_from) REFERENCES subscriptions(id)
    );
  ');

  $db->exec('
    CREATE TABLE IF NOT EXISTS payments(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      subscription_id INTEGER NOT NULL,
      method TEXT NOT NULL,
      amount_minor INTEGER NOT NULL,
      currency TEXT NOT NULL,
      status TEXT NOT NULL,
      provider_ref TEXT,
      reference_code TEXT,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      paid_at TEXT,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY(subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
    );
  ');

  $db->exec('
    CREATE TABLE IF NOT EXISTS conversations(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      title TEXT,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    );
  ');

  $db->exec('
    CREATE TABLE IF NOT EXISTS conversation_messages(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      conversation_id INTEGER NOT NULL,
      role TEXT NOT NULL,
      text TEXT NOT NULL,
      lang TEXT,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    );
  ');


  $db->exec('
    CREATE TABLE IF NOT EXISTS consents(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      policy_version TEXT NOT NULL,
      age_confirmed INTEGER NOT NULL DEFAULT 0,
      accepted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      ip_hash TEXT,
      ua_hash TEXT,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    );
  ');

  $db->exec('
    CREATE TABLE IF NOT EXISTS traffic_events(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER,
      session_id TEXT,
      event_type TEXT NOT NULL,
      path TEXT,
      ip_hash TEXT,
      ua_hash TEXT,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    );
  ');

  // Visitors tracking table with complete details
  $db->exec('
    CREATE TABLE IF NOT EXISTS visitors(
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ip_address TEXT NOT NULL,
      ip_hash TEXT,
      country TEXT,
      country_code TEXT,
      city TEXT,
      region TEXT,
      timezone TEXT,
      isp TEXT,
      user_agent TEXT,
      browser TEXT,
      browser_version TEXT,
      os TEXT,
      os_version TEXT,
      device_type TEXT,
      screen_resolution TEXT,
      referrer TEXT,
      landing_page TEXT,
      page_views INTEGER DEFAULT 1,
      session_duration INTEGER,
      user_id INTEGER,
      is_bot INTEGER DEFAULT 0,
      visit_date DATE NOT NULL,
      first_visit_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      last_activity_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    );
  ');

  // Index for fast queries
  $db->exec('CREATE INDEX IF NOT EXISTS idx_visitors_date ON visitors(visit_date);');
  $db->exec('CREATE INDEX IF NOT EXISTS idx_visitors_country ON visitors(country_code);');
  $db->exec('CREATE INDEX IF NOT EXISTS idx_visitors_ip ON visitors(ip_address);');

  db_migrate_columns();
  db_seed();
}

function db_seed(): void
{
  global $CONFIG;
  $db = db();

  $countPlans = (int) $db->querySingle("SELECT COUNT(*) FROM plans");
  if ($countPlans === 0) {
    $currency = $CONFIG['payments']['currency'] ?? 'GBP';
    $plans = [
      ['1 Month', 30, 1999],
      ['6 Months', 182, 9999],
      ['1 Year', 365, 17999],
    ];
    $stmt = $db->prepare("INSERT INTO plans(name,duration_days,price_minor,currency,active) VALUES(:n,:d,:p,:c,1)");
    if ($stmt === false) {
      error_log("DB SEED ERROR: Failed to prepare plans insert. Last error: " . $db->lastErrorMsg());
      return;
    }
    foreach ($plans as $p) {
      $stmt->bindValue(':n', $p[0], SQLITE3_TEXT);
      $stmt->bindValue(':d', $p[1], SQLITE3_INTEGER);
      $stmt->bindValue(':p', $p[2], SQLITE3_INTEGER);
      $stmt->bindValue(':c', $currency, SQLITE3_TEXT);
      $stmt->execute();
    }
  }

  $admin = $CONFIG['app']['admin_seed'];
  $exists = $db->querySingle("SELECT id FROM users WHERE username='" . SQLite3::escapeString($admin['username']) . "'");
  if (!$exists) {
    $hash = password_hash($admin['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users(username,passhash,role,email_verified) VALUES(:u,:p,'admin',1)");
    $stmt->bindValue(':u', $admin['username'], SQLITE3_TEXT);
    $stmt->bindValue(':p', $hash, SQLITE3_TEXT);
    $stmt->execute();
  }

  $demo = $CONFIG['app']['demo_user'];
  $exists2 = $db->querySingle("SELECT id FROM users WHERE username='" . SQLite3::escapeString($demo['username']) . "'");
  if (!$exists2) {
    $hash = password_hash($demo['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users(username,passhash,role,email_verified) VALUES(:u,:p,'demo',1)");
    $stmt->bindValue(':u', $demo['username'], SQLITE3_TEXT);
    $stmt->bindValue(':p', $hash, SQLITE3_TEXT);
    $stmt->execute();
  }
}


function db_migrate_columns(): void
{
  $db = db();

  // users.age_confirmed
  $cols = [];
  $res = $db->query("PRAGMA table_info(users)");
  while ($r = $res->fetchArray(SQLITE3_ASSOC))
    $cols[$r['name']] = true;
  if (empty($cols['age_confirmed'])) {
    @$db->exec("ALTER TABLE users ADD COLUMN age_confirmed INTEGER NOT NULL DEFAULT 0");
  }

  // users.reset_token (for password reset)
  if (empty($cols['reset_token'])) {
    @$db->exec("ALTER TABLE users ADD COLUMN reset_token TEXT");
  }

  // users.reset_expires (for password reset expiry)
  if (empty($cols['reset_expires'])) {
    @$db->exec("ALTER TABLE users ADD COLUMN reset_expires TEXT");
  }

  // users.verify_token (for email verification)
  if (empty($cols['verify_token'])) {
    @$db->exec("ALTER TABLE users ADD COLUMN verify_token TEXT");
  }

  // consents.age_confirmed
  $cols2 = [];
  $res2 = $db->query("PRAGMA table_info(consents)");
  while ($r = $res2->fetchArray(SQLITE3_ASSOC))
    $cols2[$r['name']] = true;
  if (empty($cols2['age_confirmed'])) {
    @$db->exec("ALTER TABLE consents ADD COLUMN age_confirmed INTEGER NOT NULL DEFAULT 0");
  }
}

