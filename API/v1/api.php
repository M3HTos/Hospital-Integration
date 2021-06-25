<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/classes/Database.php");

class Api
{
    public $params;

    public function __construct() {
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: *');
        header('Content-Type: application/json; charset=utf-8');
        require_once($_SERVER['DOCUMENT_ROOT'] . "/config.php");
        $this->params = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
        $this->db = new Database($host, $db, $user, $password);
        $this->conn = $this->db->getConnection();
    }

    protected function response($data, $status = 500) {
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
        return json_encode($data);
    }

    private function requestStatus($code) {
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code])?$status[$code]:$status[500];
    }

    public function getAction()
    {
        switch ($this->params[2]) {
            case "organizations":
                return $this->GetMedicalOrganizations();
                break;
            case "tickets":
                return $this->GetTickets();
                break;
            default:
                return $this->WrongAction();
        }
    }

    private function GetTickets() {
        $post = json_decode(file_get_contents('php://input'), true);
        $sql = "
            SELECT id, date, time_start, time_end, taken 
            FROM public.\"Tickets\"
            WHERE id_doctor = :id_doc
              AND id_hospital = :id_hosp
              AND date BETWEEN :start AND :end;";
        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            "id_doc" => $post["id_doctor"],
            "id_hosp" => $post["id_hospital"],
            "start" => $post["start_date"],
            "end" => $post["end_date"]
        ]);

        $data = array(
            "success" => true,
            "data" => []
        );

        foreach ($stmt->fetchAll() as $row) {
            array_push(
                $data["data"],
                array(
                    "id_ticket" => $row["id"],
                    "date" => $row["date"],
                    "time_start" => $row["time_start"],
                    "time_end" => $row["time_end"],
                    "is_closed" => $row["taken"]
                )
            );
        }
        return $this->response($data, 200);
    }

    private function GetMedicalOrganizations() {
        # Немного не понял момента с несколькими поликлиниками у одной организации. Добавил табличку,
        # в которой хранится связь между поликлиниками и организациями, и присоединил в запросе.
        $sql = "
            SELECT id_hospital, name_organization, address FROM public.\"Hospitals\"
            JOIN public.\"Organizations\" 
            USING (id_organization);";
        $stmt = $this->conn->prepare($sql);

        $stmt->execute();

        $data = array(
            "success" => true,
            "data" => []
        );

        foreach ($stmt->fetchAll() as $row) {
            array_push(
                $data["data"],
                array(
                    "id_hospital" => $row["id_hospital"],
                    "address" => $row["address"],
                    "name_organization" => $row["name_organization"]
                )
            );
        }
        return $this->response($data, 200);
    }

    private function WrongAction() {
        $data = array(
            "success" => false,
            "error" => "Wrong action"
        );
        return $this->response($data, 200);
    }
}

try {
    $api = new Api();
    echo $api->getAction();
} catch (Exception $e) {
    # Возвращение просто необработанной информации из эксепшена просто для отладки
    echo json_encode(Array('error' => $e->getMessage())); 
}