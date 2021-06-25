<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use Aspera\Spreadsheet\XLSX\Reader;
use Aspera\Spreadsheet\XLSX\SharedStringsConfiguration;


class Uploader {
    private $conn;

    function __construct($database) {

        $this->conn = $database->getConnection();
    }

    function upload_xlsx($name) {
        $options = array(
            'TempDir'                    => './for_upload/temp/',
            'SkipEmptyCells'             => true,  
            'ReturnDateTimeObjects'      => false,
            'SharedStringsConfiguration' => new SharedStringsConfiguration()
        );

        $reader = new Reader($options);
        $reader->open(vsprintf('./for_upload/%s', [$name]));
        $i = 0;
        foreach ($reader as $row) {
            if (count($row) > 1) {
                if (++$i == 1) continue;
                $this->parse_row($row);
            }
        }
        $reader->close();
        }

    function parse_row($row) {
        $keys = array("hosp_id", "address", "doc_id", "doc_name", "date", "time_start", "time_end", "flag");
        extract(array_combine($keys, $row));
        $this->check_for_new_hospital($hosp_id, $address);
        $this->check_for_new_doctor($hosp_id, $doc_id, $doc_name);
        $this->insert_ticket($hosp_id, $doc_id, $date, $time_start, $time_end, $flag);
    }

    function check_for_new_hospital($hosp_id, $address) {
        $sql = "
        INSERT INTO public.\"Hospitals\" (id_hospital, id_organization, address) 
            VALUES (:id, :org, :address) 
            ON CONFLICT (id_hospital) DO NOTHING";
        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':id', $hosp_id);
        $stmt->bindValue(':address', $address);
        $stmt->bindValue(':org', 3);
        
        $stmt->execute();
    }

    function check_for_new_doctor($hosp_id, $doc_id, $doc_name) {
        $sql = "
        INSERT INTO public.\"Doctors\" (id_hospital, id_doctor, name_doctor) 
            VALUES (:hosp, :doc, :name) 
            ON CONFLICT (id_doctor) DO NOTHING;";
        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':hosp', $hosp_id);
        $stmt->bindValue(':name', $doc_name);
        $stmt->bindValue(':doc', $doc_id);
        
        $stmt->execute();
    }

    function insert_ticket($hosp_id, $doc_id, $date, $time_start, $time_end, $flag) {
        $sql = "
        INSERT INTO public.\"Tickets\" (id_hospital, id_doctor, date, time_start, time_end, taken) 
            VALUES (:hosp, :doc, :date, :time_start, :time_end, :taken);";
        $stmt = $this->conn->prepare($sql);

        if ($flag == "занят") {
            $taken = true;
        } else {
            $taken = false;;
        }

        $stmt->bindValue(':hosp', $hosp_id);
        $stmt->bindValue(':doc', $doc_id);
        $stmt->bindValue(':date', str_replace("-", "/", $date));
        $stmt->bindValue(':time_start', $time_start);
        $stmt->bindValue(':time_end', $time_end);
        $stmt->bindValue(':taken', $taken, PDO::PARAM_BOOL);
        
        $stmt->execute();
    }
}
?>