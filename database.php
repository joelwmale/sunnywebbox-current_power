<?php

class Database {

    /**
     * Table layout
     *
     * @id int(11) (PRIMARY_KEY)
     * @severity int(2)
     * @report_time int(11)
     * @power_percent int(11)
     *
     */

    // Adjust to your database settings
    var $database_host = 'localhost';
    var $database_name = 'power';
    var $database_username = 'root';
    var $database_password = 'root';

    // This is the table at which the schema is stored
    var $report_table_name = 'report_log';

    public function connectDb() {
        return new PDO("mysql:host={$this->database_host};dbname={$this->database_name}", $this->database_username, $this->database_password);
    }

    public function getLastReport() {
        $db = $this->connectDb();

        $stmt = $db->prepare("SELECT * FROM {$this->report_table_name} ORDER BY id DESC LIMIT 1");
        $result = $stmt->execute();

        if ($result) {
            return $stmt->fetch(PDO::FETCH_OBJ);
        }

        return false;
    }

    public function addReport($severity, $report_time, $power_percent) {
        $db = $this->connectDb();

        $stmt = $db->prepare("INSERT INTO {$this->report_table_name} (severity, report_time, power_percent) VALUES (?, ?, ?)");
        $result = $stmt->execute(array($severity, $report_time, $power_percent));

        if ($result) {
            return true;
        }

        return false;
    }

    public function shouldIReport($current_power_level) {
        // Get the last report
        $last_report = $this->getLastReport();

        // The level the power was at when we last reported
        $report_power = $last_report->power_percent;

        // This is true if the script last reported over an hour ago, false if it was less than that
        $reportedOverAnHourAgo = $last_report->report_time >= strtotime('-60 minutes');

        if ((!$reportedOverAnHourAgo) && ($current_power_level <= ($report_power - 5) || $current_power_level >= ($report_power + 5))) {
            // We did not report within the last 60 minutes and the power is now +/- 5 of the last report amount.
            return true;
        } elseif ($current_power_level <= ($report_power - 3)) {
            // The current level is at least lower by 3, so just report on it.
            return true;
        }

        return false;
    }

}