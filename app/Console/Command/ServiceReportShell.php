<?php

App::uses('CakeEmail', 'Network/Email');

class ServiceReportShell extends AppShell
{

    /**
     * @var string
     */
    private $logins_query = "SELECT count(DISTINCT hasheduserid) as unique_logins,"
    . " sum(count) as total_logins, country,"
    . " countrycode, min(date) as min_date,"
    . " max(date) as max_date"
    . " FROM statistics_country_hashed"
    . " WHERE service = ':service_id:'"
    . " AND country != 'Unknown'"
    . " GROUP BY country, countrycode"
    . " order by country asc;";

    /**
     * @var string[]
     */
    public $uses = array(
        'EmailAddress',
    );

    /**
     * @var null
     */
    private $Email = null;

    /**
     * @var null
     */
    private $fromTitle = null;

    /**
     * @var int
     */
    private $wait_sec = 5;

    /**
     * @var null
     */
    private $subject = null;

    /**
     * @var null
     */
    private $message_body = null;

    /**
     * @var null
     */
    private $co_name = null;

    /**
     * @var string[]
     */
    private $from = array(
        'noreply@faai.grnet.gr' => 'RCIAM AAI Notifications',
    );

    /**
     * @var null
     */
    private $csvfile = null;

    /**
     * @var null
     */
    private $csv_file_path = null;

    /**
     *
     */
    public function main()
    {
        $command = null;
        if (!empty($this->args[0])) {
            $this->Email     = new CakeEmail('default');
            $this->co_name   = !empty($this->args[1]) ? $this->args[1] : "";
            $this->fromTitle = !empty($this->args[2]) ? $this->args[2] : "";
            if (!empty($this->fromTitle)) {
                $this->from['noreply@faai.grnet.gr'] = $this->fromTitle;
            }
            $this->subject      = !empty($this->args[3]) ? $this->args[3] : "Notification";
            $this->message_body = !empty($this->args[4]) ? $this->args[4] : "";
            $this->csvfile      = $this->args[5];
            // Execute requested action
            $command = $this->args[0];
            $fn      = 'execute_' . $command;
            if (method_exists($this, $fn)) {
                $this->$fn();
            } else {
                $this->out('This command does not exist.');
                exit;
            }
        } else {
            $this->out('Please provide action');
        }
    }

    /**
     *
     */
    public function execute_report()
    {
        $dbc = $this->EmailAddress->getDataSource();
        // Parse the csv file to an array
        $data_array = $this->parsecsv_toarray($this->csvfile);
        // Find data per entry. If any
        foreach ($data_array as $service) {
            if (empty($service['Service Identifier'])
                || empty($service['Service Contact'])) {
                continue;
            }
            $toEmails = explode("\n", $service['Service Contact']);
//            $this->out("Emails: " . print_r($toEmails, true));
            // Construct my query
            $this_service_tmp_query = str_replace(":service_id:", $service['Service Identifier'], $this->logins_query);
            // Get the results
            $results = $this->EmailAddress->query($this_service_tmp_query);
            if (empty($results)) {
                continue;
            } else {
                $results = current($results);
            }
            // Create the csv file
            $tmpfname     = tempnam("/tmp", "service_provider_login_data");
            $this->create_tmp_csv_file($results, $tmpfname);
            // Send to my email
            $this->sendEmail(
                $this->from,
                $toEmails,
                $this->subject,
                $this->message_body,
                $tmpfname
            );
            unlink($tmpfname);
        }
    }

    /**
     * @param $data
     *
     * @return false|string
     */
    public function create_tmp_csv_file($data, $tmpfname)
    {
        $headers = array();
        $fp      = null;
        if (!$fp = fopen($tmpfname, 'w+')) {
            $this->out("Could not open temp file.");
            return false;
        }

        foreach ($data as $row) {
            // Open temp file pointer
            if (empty($headers)) {
                $headers =  array_map(
                    static function($field) {
                        return Inflector::humanize($field);
                    },
                    array_keys($row));
                fputcsv($fp, $headers);
            }
            fputcsv($fp, array_values($row));
        }
        fclose($fp);
    }

    /**
     * @param $file
     *
     * @return array
     */
    public function parsecsv_toarray($file)
    {
        $parsed_data   = array();
        $headers       = null;
        $csv_file_path = $this->getFilePath($this->csvfile);
        $this->out("CSV File path : " . $csv_file_path);
        // Open the file for reading
        if (($h = fopen($csv_file_path, "r")) !== false) {
            // Each line in the file is converted into an individual array that we call $data
            // The items of the array are comma separated
            while (($data = fgetcsv($h, 4000, ",")) !== false) {
                if (empty($headers)) {
                    // These are the headers
                    $headers = $data;
                    continue;
                }
                // Each individual array is being pushed into the nested array
                $parsed_data[] = array_combine($headers, $data);
            }
            fclose($h);
        }


        return $parsed_data;
    }

    /**
     * @param $fname
     *
     * @return array|false|string|string[]|null
     */
    public function getFilePath($fname)
    {
        $raw_output = shell_exec("find ./ -name '" . $fname . "' -type f|xargs realpath");

        // Remove new lines
        return str_replace(array("\n\r", "\n", "\r"), "", $raw_output);
    }


    /**
     * @param $fromMail
     * @param $toMail
     * @param $subject
     * @param $messageBody
     * @param $attachment
     */
    public function sendEmail(
        $fromMail,
        $toMail,
        $subject,
        $messageBody,
        $attachment
    ) {
        $this->out('Sending email to:' . $toMail);
        $this->Email->from($fromMail)
            ->emailFormat('both')
            ->to($toMail)
            ->subject($subject)
            ->template('custom', 'basic')
            ->viewVars(array('text' => $messageBody))
            ->attachments(
                array(
                    "report" => array(
                        "file"      => $attachment,
                        "mimetype"  => "text/csv",
                    ),
                )
            );
        $this->Email->send();
        $this->out('Wait ' . $this->wait_sec . 'sec ...');
        sleep($this->wait_sec);
    }

}


