<?php
/**
 * Created by PhpStorm.
 * User: Joel.Male
 * Date: 30/05/2017
 * Time: 7:29 AM
 */

class Database {

    public function connectDb() {
        return new PDO('mysql:host=localhost;dbname=greenswamp_power', 'root', 'ufrp2398');
    }

    public function getLastReport() {
        $db = $this->connectDb();

        $stmt = $db->prepare("SELECT * FROM report_log ORDER BY id DESC LIMIT 1");
        $result = $stmt->execute();

        if ($result) {
            return $stmt->fetch(PDO::FETCH_OBJ);
        }

        return false;
    }

    public function addReport($severity, $report_time, $power_percent) {
        $db = $this->connectDb();

        $stmt = $db->prepare("INSERT INTO report_log (severity, report_time, power_percent) VALUES (?, ?, ?)");
        $result = $stmt->execute(array($severity, $report_time, $power_percent));

        if ($result) {
            return true;
        }

        return false;
    }

    public function shouldIReport($current_power_level) {
        $last_report = $this->getLastReport();

        if ($current_power_level == $last_report->power_percent ) {
            return false;
        } elseif (strtotime('-30 minutes') >= ($last_report->report_time)) {
            return true;
        } else {
            return false;
        }
    }

}