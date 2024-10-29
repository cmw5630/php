<?php
/**
 * # 메일발송 테스트 
 */
namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AmaZonSes extends Mailable {
  
    use Queueable, SerializesModels;
    public $email_content;
   
    public function __construct($email_content) {
        $this->email_content = $email_content;                    
    }

    public function build() {        
        return $this->from(env('MAIL_FROM_ADDRESS'))->view('mail')->with([
            'content' => $this->email_content['content'],
        ])->subject($this->email_content['subject']);

    }
} 
?>