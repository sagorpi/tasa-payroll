<?php
/**
 * Database Handler Class
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TASA Payroll Database Class
 */
class TASA_Payroll_Database {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Table name
     */
    private $table_name;

    /**
     * Employee details table name
     */
    private $employee_table_name;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tasa_payroll';
        $this->employee_table_name = $wpdb->prefix . 'tasa_employee_details';
    }
    
    /**
     * Get table name
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Get employee details table name
     */
    public function get_employee_table_name() {
        return $this->employee_table_name;
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tasa_payroll';
        $employee_table_name = $wpdb->prefix . 'tasa_employee_details';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            month tinyint(2) NOT NULL,
            year smallint(4) NOT NULL,
            total_working_days tinyint(2) NOT NULL,
            days_absent decimal(4,1) NOT NULL DEFAULT 0,
            monthly_salary decimal(10,2) NOT NULL,
            per_day_salary decimal(10,2) NOT NULL,
            bonus decimal(10,2) NOT NULL DEFAULT 0,
            income_tax decimal(10,2) NOT NULL DEFAULT 0,
            provident_fund decimal(10,2) NOT NULL DEFAULT 0,
            final_salary decimal(10,2) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_month_year (user_id, month, year),
            KEY user_id (user_id),
            KEY month_year (month, year)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $employee_sql = "CREATE TABLE IF NOT EXISTS $employee_table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            base_salary decimal(10,2) NOT NULL DEFAULT 0,
            employee_id varchar(100) DEFAULT NULL,
            phone_number varchar(30) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY employee_id (employee_id)
        ) $charset_collate;";
        dbDelta($employee_sql);
        
        // Update version
        update_option('tasa_payroll_db_version', TASA_PAYROLL_VERSION);
    }
    
    /**
     * Insert payroll record
     */
    public function insert_payroll($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%d', // user_id
                '%d', // month
                '%d', // year
                '%d', // total_working_days
                '%f', // days_absent
                '%f', // monthly_salary
                '%f', // per_day_salary
                '%f', // bonus
                '%f', // income_tax
                '%f', // provident_fund
                '%f', // final_salary
                '%s', // created_at
                '%s', // updated_at
                '%d', // created_by
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update payroll record
     */
    public function update_payroll($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            array(
                '%d', // user_id
                '%d', // month
                '%d', // year
                '%d', // total_working_days
                '%f', // days_absent
                '%f', // monthly_salary
                '%f', // per_day_salary
                '%f', // bonus
                '%f', // income_tax
                '%f', // provident_fund
                '%f', // final_salary
                '%s', // updated_at
            ),
            array('%d')
        );
    }
    
    /**
     * Get payroll record by ID
     */
    public function get_payroll($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
    }
    
    /**
     * Get payroll records by user ID
     */
    public function get_user_payrolls($user_id, $month = null, $year = null) {
        global $wpdb;

        $where = $wpdb->prepare("user_id = %d", $user_id);

        if ($month !== null) {
            $where .= $wpdb->prepare(" AND month = %d", $month);
        }

        if ($year !== null) {
            $where .= $wpdb->prepare(" AND year = %d", $year);
        }

        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY year DESC, month DESC"
        );
    }

    /**
     * Get all payroll records with pagination
     */
    public function get_all_payrolls($limit = 20, $offset = 0, $search = '') {
        global $wpdb;

        $where = "1=1";

        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (
                    user_id IN (
                        SELECT ID FROM {$wpdb->users} WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s
                    )
                    OR user_id IN (
                        SELECT user_id FROM {$this->employee_table_name} WHERE employee_id LIKE %s
                    )
                )",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY year DESC, month DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    /**
     * Get total count of payroll records
     */
    public function get_total_count($search = '') {
        global $wpdb;

        $where = "1=1";

        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (
                    user_id IN (
                        SELECT ID FROM {$wpdb->users} WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s
                    )
                    OR user_id IN (
                        SELECT user_id FROM {$this->employee_table_name} WHERE employee_id LIKE %s
                    )
                )",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}"
        );
    }

    /**
     * Delete payroll record
     */
    public function delete_payroll($id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Check if payroll exists for user in month/year
     */
    public function payroll_exists($user_id, $month, $year, $exclude_id = null) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE user_id = %d AND month = %d AND year = %d",
            $user_id,
            $month,
            $year
        );

        if ($exclude_id) {
            $sql .= $wpdb->prepare(" AND id != %d", $exclude_id);
        }

        return $wpdb->get_var($sql);
    }

    /**
     * Get employee details by user ID
     */
    public function get_employee_detail($user_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->employee_table_name} WHERE user_id = %d",
                $user_id
            )
        );
    }

    /**
     * Get employee details keyed by user ID
     */
    public function get_employee_details_by_user_ids($user_ids) {
        global $wpdb;

        $user_ids = array_filter(array_map('intval', (array) $user_ids));
        if (empty($user_ids)) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->employee_table_name} WHERE user_id IN ($placeholders)",
            ...$user_ids
        );

        $rows = $wpdb->get_results($query);
        $result = array();

        foreach ($rows as $row) {
            $result[(int) $row->user_id] = $row;
        }

        return $result;
    }

    /**
     * Insert or update employee details for user
     */
    public function upsert_employee_detail($user_id, $base_salary, $employee_id = '', $phone_number = '') {
        global $wpdb;

        $existing = $this->get_employee_detail($user_id);
        $data = array(
            'user_id' => (int) $user_id,
            'base_salary' => (float) $base_salary,
            'employee_id' => $employee_id !== '' ? $employee_id : null,
            'phone_number' => $phone_number !== '' ? $phone_number : null,
            'updated_at' => current_time('mysql'),
        );

        if ($existing) {
            return $wpdb->update(
                $this->employee_table_name,
                $data,
                array('user_id' => (int) $user_id),
                array('%d', '%f', '%s', '%s', '%s'),
                array('%d')
            );
        }

        $data['created_at'] = current_time('mysql');

        $result = $wpdb->insert(
            $this->employee_table_name,
            $data,
            array('%d', '%f', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get employees (WordPress users + employee details) with pagination
     */
    public function get_employees_with_details($limit = 20, $offset = 0, $search = '') {
        global $wpdb;

        $employee_table = $this->employee_table_name;
        $users_table = $wpdb->users;

        $where = '1=1';
        $params = array();

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= ' AND (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s OR e.employee_id LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $params[] = (int) $limit;
        $params[] = (int) $offset;

        $sql = "SELECT u.ID, u.user_login, u.display_name, u.user_email, e.base_salary, e.employee_id, e.phone_number
                FROM {$users_table} u
                LEFT JOIN {$employee_table} e ON u.ID = e.user_id
                WHERE {$where}
                ORDER BY u.display_name ASC
                LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Get total user count for employee listing
     */
    public function get_total_employee_count($search = '') {
        global $wpdb;

        $employee_table = $this->employee_table_name;
        $users_table = $wpdb->users;
        $where = '1=1';

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(
                ' AND (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s OR e.employee_id LIKE %s)',
                $like,
                $like,
                $like,
                $like
            );
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$users_table} u LEFT JOIN {$employee_table} e ON u.ID = e.user_id WHERE {$where}");
    }
}
