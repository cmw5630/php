<?php
namespace App\Http\Controllers\API\v1\User;

use Illuminate\Http\Request;
use App\Mails\AmaZonSes;
use Illuminate\Support\Facades\Mail;



class MailController 
{  
public function mailtest (Request $request) {
    $input = $request->all();   
    
      // single email
      $data = array(
          'target_email' => array(
               // 수신인정보 
               array(
                  'name' => 'Member Name',
                  'email' => $input['email']
               )
            //    , // multi email
            //    array(
            //     'name' => '지메일잡스',
            //     'email' => 'sjwiq200@gmail.com'
            //     )
          ),
          'sender' => 'B2G Game',
          'subject' => 'B2G Game 회원가입 인증메일입니다.',
          'content' => '메일 내용부분 '
      );      
      return $this->sendMail($data);
  }

  /**
   * @param $options
   * $options['target_email']
   * $options['subject']
   * $options['content']
   * @return string
   */
  public function sendMail($options) {    
    //메일발송 테스트 완료
    Mail::to($options['target_email'])->send(new AmaZonSes(array(
        'subject' => $options['subject'],
        'content' => $options['content'],
        'sender' => $options['sender']
    )));    

    return 'Done!';
  }

}
