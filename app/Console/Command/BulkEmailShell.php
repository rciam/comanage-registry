<?php

App::uses('CakeEmail', 'Network/Email');

class BulkEmailShell extends AppShell {

    /**
     * @var string
     */
    private $igtf_query = <<<EOT
select distinct string_agg(DISTINCT names.given, ',')   as given,
                string_agg(DISTINCT names.family, ',')  as family,
                string_agg(DISTINCT mail.mail, ',')     as pemail,
                people.id                               as pid,
                string_agg(DISTINCT ci.identifier, ',') as ident,
                string_agg(DISTINCT people.status, ',') as pstatus,
                string_agg(DISTINCT cos.name, ',')      as CO
from cm_email_addresses as mail
         inner join cm_names names
                    on mail.co_person_id = names.co_person_id and not mail.deleted and mail.email_address_id is null and
                       mail.type = 'official' and mail.verified = true
         inner join cm_co_people as people
                    on people.id = mail.co_person_id and people.status = 'A' and
                       not people.deleted and people.co_person_id is null
         inner join cm_identifiers as ci
                    on people.id = ci.co_person_id and not ci.deleted and ci.identifier_id is null
         inner join cm_cos as cos
                    on people.co_id = cos.id and cos.status = 'A'
         inner join cm_co_org_identity_links as links
                    on people.id = links.co_person_id and not links.deleted and links.co_org_identity_link_id is null
         inner join cm_org_identities as oid
                    on oid.id = links.org_identity_id and not oid.deleted and oid.org_identity_id is null
         inner join cm_email_addresses as mailOid
                    on mailOid.org_identity_id = oid.id and not mailOid.deleted and mailOid.email_address_id is null and
                       mailOid.type = 'official'
         inner join cm_certs as cc on cc.org_identity_id = links.org_identity_id and not cc.deleted and cc.cert_id is null
where mail.verified = true
  and mailOid.verified = true
  and oid.authn_authority = 'https://edugain-proxy.igtf.net/simplesaml/saml2/idp/metadata.php'
  and ci.type ilike 'epuid'
  and ci.status = 'A'
  and cos.name = '%co_name%'
  and (cc.issuer = '' or cc.issuer is null)
GROUP BY people.id;
EOT;

    /**
     * @var string[]
     */
    public $uses = array(
        'EmailAddress'
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
    private $wait_sec = 30;

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
        'noreply@faai.grnet.gr' => 'RCIAM AAI Notifications'
    );

    /**
     *
     */
    public function main() {
        $command = null;
        if(!empty($this->args[0])) {
            $this->Email = new CakeEmail('default');
            $this->co_name = !empty($this->args[1]) ? $this->args[1] : "";
            $this->fromTitle = !empty($this->args[2]) ? $this->args[2] : "";
            if(!empty($this->fromTitle)) {
                  $this->from['noreply@faai.grnet.gr'] = $this->fromTitle;
            }
            $this->subject = !empty($this->args[3]) ? $this->args[3] : "Notification";
            $this->message_body = !empty($this->args[4]) ? $this->args[4] : "";
            // Execute requested action
            $command = $this->args[0];
            $fn = 'execute_' . $command;
            if(method_exists($this, $fn)) {
                $this->$fn();
            }
            else {
                $this->out('This command does not exist.');
                exit;
            }
        }
        else {
            $this->out('Please provide action');
        }
    }

    /**
     *
     */
    public function execute_igtf_update() {
        $dbc = $this->EmailAddress->getDataSource();
        try {
            $igtf_query = str_replace('%co_name%', $this->co_name, $this->igtf_query);
            $results = $this->EmailAddress->query($igtf_query);
            $email_list = array();
            if(!empty($results)) {
                $email_list = Hash::combine(
                    $results,
                    '{n}.{n}.pemail',
                    array('%s %s', '{n}.{n}.given', '{n}.{n}.family')
                );
            }
            foreach($email_list as $mail => $username) {
                $current_body = $this->message_body;
                if(strpos($current_body, '%username%') !== false) {
                    $current_body = str_replace('%username%', $username, $current_body);
                }

                $this->sendEmail(
                    $this->from,
                    $mail,
                    $this->subject,
                    $current_body
                );
            }
        }
        catch(Exception $e) {
            $this->out($e->getMessage());
        }
    }

    /**
     * @param $fromMail
     * @param $toMail
     * @param $subject
     * @param $messageBody
     */
    public function sendEmail(
        $fromMail,
        $toMail,
        $subject,
        $messageBody
    ){

        $this->out('Sending email to:' . $toMail);
        $this->Email->from($fromMail)
            ->emailFormat('html')
            ->to($toMail)
            ->subject($subject)
            ->send($messageBody);
        $this->out('Wait ' . $this->wait_sec . 'sec ...');
        sleep($this->wait_sec);
    }
}


