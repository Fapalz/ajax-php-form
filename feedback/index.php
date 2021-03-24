<?php
header('Content-Type: application/json');
// обработка только ajax запросов (при других запросах завершаем выполнение скрипта)
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
  exit();
}
// обработка данных, посланных только методом POST (при остальных методах завершаем выполнение скрипта)
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  exit();
}

$ref = $_SERVER['HTTP_REFERER'];
$form = array();


$form['form-1'] = array(
	'fields' => array(
		'name' => array(
			'title' => 'Имя',
			'validate' => array(
				'preg' => '%[A-Z-a-zА-Яа-я\s]%',
				'minlength' => '3',
				'maxlength' => '35',
			),
			'messages' => array(
				'preg' => 'Поле [ %1$s ] возможно содержит ошибку',
				'minlength' => 'Минимальная длинна поля [ %1$s ] меньше допустимой - %2$s',
				'maxlength' => 'Максимальная длинна поля [ %1$s ] превышает допустимую - %2$s',
			)
		),
		'message' => array(
			'title' => 'Сообщение',
			'validate' => array(
				'minlength' => '3',
				'maxlength' => '35',
			),
			'messages' => array(
				'preg' => 'Поле [ %1$s ] возможно содержит ошибку',
				'minlength' => 'Минимальная длинна поля [ %1$s ] меньше допустимой - %2$s',
				'maxlength' => 'Максимальная длинна поля [ %1$s ] превышает допустимую - %2$s',
			)
		),
    'email' => array(
			'title' => 'Email',
			'validate' => array(
				'preg' => "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/",
        'required' => true,
			),
			'messages' => array(
				'preg' => 'Поле [ %1$s ] возможно содержит ошибку',
        'required' => 'Email обязателен для заполнения'
			)
		),
    'uploaded_file' => array(
			'title' => 'Файлы',
      'file' => true,
			'validate' => array(
				'extensions' => 'png,jpg,tif,pdf,doc,docs',
        'maxsize' => '10000000'
			),
			'messages' => array(
				'extensions' => 'Допускаются файлы с расширением [ %1$s ]',
				'maxsize' => 'Максимальный размер файлов [ %1$s ]',
			)
		),
	),
	'config' => array(
		'charset' => 'utf-8',
		'subject' => 'Тема письма',
		'title' => 'Заголовок в теле письма',
		'ajax' => true,
		'validate' => true,
		'from_email' => 'blacesmot@gmail.com',
		'from_name' => 'Кирилл',
		'to_email' => 'blacesmot@gmail.com',
		'to_name' => 'Кирилл',
		'referer' => true,
		'tpl' => true,
    'captcha' => true,
    'log' => true,
		'geoip' => true,
		'type' => 'html',
		'antispam' => 'email77',
		'antispamjs' => 'address77',
		'okay' => 'Сообщение отправлено - OK',
		'fuck' => 'Сообщение отправлено - ERROR',
		'spam' => 'Cпам робот',
		'notify' => 'color-modal-textbox',
		'usepresuf' => false
	)
);

// 2 ЭТАП - ПОДКЛЮЧЕНИЕ PHPMAILER
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once('phpmailer/src/Exception.php');
require_once('phpmailer/src/PHPMailer.php');
require_once('phpmailer/src/SMTP.php');



// 3 ЭТАП - ОТКРЫТИЕ СЕССИИ И ИНИЦИАЛИЗАЦИЯ ПЕРЕМЕННОЙ ДЛЯ ХРАНЕНИЯ РЕЗУЛЬТАТОВ ОБРАБОТКИ ФОРМЫ
session_start();
$data = array();
$error = array();
$act = isset($_REQUEST['form-id']) ? $_REQUEST['form-id'] : die('error');
$data['result'] = 'success';


// if ($_FILES['uploaded_file']) {
//   echo json_encode($_FILES);
    
//     die();
// }

if(isset($form[$act])) {

   $form = $form[$act];
   $getdata = array();
   $getFiles = array();

    foreach($form['fields'] as $name => $field) {
      $title = (isset($field['title'])) ? $field['title'] : $name;
      
      $def = 'Поле с именем [ '.$name.' ] содержит ошибку.';

      
      if($field['file'] === true) {
        
        if(empty($_FILES[$name])) { continue; }

        $files = array();

        if(is_array($_FILES[$name]['name'])) {
          foreach($_FILES[$name]['name'] as $key => $value) {
            if ( $_FILES[$name]['error'][$key] > 0) { continue; }

            $files[$key]['name'] = $_FILES[$name]['name'][$key];
            $files[$key]['size'] = $_FILES[$name]['size'][$key];
            $files[$key]['tmp_name'] = $_FILES[$name]['tmp_name'][$key];
            $files[$key]['type'] = $_FILES[$name]['type'][$key];
            $files[$key]['error'] = $_FILES[$name]['error'][$key];
          }
        } else {
          if($_FILES[$name]['error'] == 0) {
            $files[] = $_FILES[$name];
          }
        }

        if($field['validate']) {

          if(isset($field['validate']['required']) && empty($files)) {
            $error[$name] = isset($field['messages']['required']) ? sprintf($field['messages']['required'], $title) : $def;
            
            continue;
          }

          if(!empty($files) && isset($field['validate']['maxsize'])) {
            $sizes = 0;

            foreach($files as $key => $val) {
              $sizes += floatval($val['size']);
            }
            
            if ($sizes > $field['validate']['maxsize']) {
              $error[$name][] = isset($field['messages']['maxsize']) ? sprintf($field['messages']['maxsize'], $title, $field['validate']['maxsize']) : $def;
            }
          }

          foreach($files as $key => $val) {
            $fileName = $val['name'];

            if(!empty($files) && isset($field['validate']['extensions'])) {
              $extensionsArr = explode(',', $field['validate']['extensions']);
              // echo json_encode($extensionsArr);
              // die();
              $ext = strtolower(substr($fileName, strpos($fileName,'.') + 1, strlen($fileName)-1));
              
              if(!in_array($ext, $extensionsArr)) {
                $error[$name][] = isset($field['messages']['extensions']) ? sprintf($field['messages']['extensions'], $field['validate']['extensions']) : $def;
              }
            }
          }
        }

        $getFiles = array_merge($getFiles, $files);
        continue;
      }

      $getdata[$name]['title'] = $title;
      $rawdata = isset($_POST[$name]) ? trim($_POST[$name]) : '';

        if(isset($field['validate'])) {
            
            
            // -0-
            if(isset($field['validate']['required']) &&
                empty($rawdata)) {
                $error[$name] = isset($field['messages']['required']) ? sprintf($field['messages']['required'], $title) :
                                (isset($messages['validator']['required']) ? sprintf($messages['validator']['required'], $title) : $def);
                $data['result'] = 'error';
            }
            // -1-
            if(isset($field['validate']['minlength']) &&
                mb_strlen($rawdata) < $field['validate']['minlength']) {
                $error[$name] = isset($field['messages']['minlength']) ? sprintf($field['messages']['minlength'], $title, $field['validate']['minlength']) : $def;
                $data['result'] = 'error';
            }
            // -2-
            if(isset($field['validate']['maxlength']) &&
              mb_strlen($rawdata) > $field['validate']['maxlength']) {
                  $error[$name] = isset($field['messages']['maxlength']) ? sprintf($field['messages']['maxlength'], $title, $field['validate']['maxlength']) : $def;
                  $data['result'] = 'error';
            }
            // -3-
            if(isset($field['validate']['preg']) && mb_strlen($rawdata) > 0 &&
                !preg_match($field['validate']['preg'], $rawdata)) {
                $error[$name] = isset($field['messages']['preg']) ? sprintf($field['messages']['preg'], $title, $field['validate']['preg']) : $def;
                $data['result'] = 'error';
            }
            // -4-
            if(isset($field['validate']['substr']) &&
                mb_strlen($rawdata) > $field['validate']['substr']) {
                $rawdata = mb_substr($rawdata, 0, $field['validate']['substr']);
            }

          $outdata = htmlspecialchars($rawdata);

          $getdata[$name]['value'] = $outdata;

        }

        else {
          $getdata[$name]['value'] = htmlspecialchars($rawdata);
        }

        if(empty($getdata[$name]['value'])) {
              unset($getdata[$name]);
        }
    }

    if ($form['config']['captcha'] == true) {
      if (isset($_POST['captcha']) && isset($_SESSION['captcha'])) {
        $captcha = filter_var($_POST['captcha'], FILTER_SANITIZE_STRING); // защита от XSS
        if ($_SESSION['captcha'] != $captcha) { // проверка капчи
          $error['captcha'] = 'Код не соответствует изображению.';
          $data['result'] = 'error';
        }
      } else {
        $error['captcha'] = 'Ошибка при проверке кода';
        $data['result'] = 'error';
      }
    }


    /* if(isset($form['cfg']['antispam']) && isset($_POST[$form['cfg']['antispam']])) {
        if(!empty($_POST[$form['cfg']['antispam']])) {
         $error[] = $form['cfg']['spam'];
        }
    }
     if(isset($form['cfg']['antispamjs']) && isset($_POST[$form['cfg']['antispamjs']])) {
         if(!empty($_POST[$form['cfg']['antispamjs']])) {
             $error[] = $form['cfg']['spam'];
         }
     } */


    if($data['result'] == 'success') {

      $bodyMail = '';

      if($form['config']['tpl']) {
        $out = tpl(array('name' => $act, 'getdata' => $getdata, 'config' => $form['config']));
        if(is_string($out)) {
          $bodyMail = $out;
        }
      }

       if(mb_strlen(trim($bodyMail)) < 10) {
          if(isset($form['config']['title'])) {
            $bodyMail .= $form['config']['title']."\r\n\r\n";
          }
          foreach($getdata as $name => $item) {
              $bodyMail .= $item['title'].": ".$item['value']."\r\n";
          }
          if($form['config']['referer']) {
            $bodyMail .= "\r\n\r\n\r\n\r\n".$ref;
          }
      }


      // устанавливаем параметры
      $mail = new PHPMailer;
      $mail->setLanguage("ru");

      //$mail->SMTPDebug = 3; 
      $mail->isSMTP();
      //Set SMTP host name                          
      $mail->Host = "smtp.gmail.com";
      //Set this to true if SMTP host requires authentication to send email
      $mail->SMTPAuth = true;                          
      //Provide username and password     
      $mail->Username = "blacesmot@gmail.com";                 
      $mail->Password = "nifcfkllertghwbq";                           
      //If SMTP requires TLS encryption then set it
      $mail->SMTPSecure = "tls";                           
      //Set TCP port to connect to
      $mail->Port = 587; 

      // $mail->From = $form['config']['from_email'];
      // $mail->FromName = $form['config']['from_name'];
      // $mail->Subject = $form['config']['subject'];

      $mail->CharSet = 'UTF-8';
      $mail->IsHTML(true);
      $fromName = '=?UTF-8?B?'.base64_encode($form['config']['from_name']).'?=';
      $mail->setFrom($form['config']['from_email'], $fromName);
      $mail->Subject = '=?UTF-8?B?'.base64_encode($form['config']['subject']).'?=';
      $mail->Body = $bodyMail;

      $emails = explode(",", $form['config']['to_email']);

      foreach($emails as $email) {
        $mail->addAddress(trim($email));
      }

      foreach($getFiles as $file) {
        $mail->AddAttachment($file['tmp_name'], $file['name']);
      }


      try {
          $mail->send();
      } catch (Exception $e) {
        $data['result'] = 'error';
        $data['form_error'] = $mail->ErrorInfo;
         
      }

      // отправляем письмо
      // if (!$mail->send()) {
      //   $data['result'] = 'error';
      // }
    }


    if ($data['result'] == 'success' && $form['config']['log']) {
      try { 
        $output = "---------------------------------" . "\n";
        $output .= date("d-m-Y H:i:s") . "\n";
        foreach($getdata as $name => $item) {
            $output .= $item['title'].": ".$item['value']."\r\n";
        }
        file_put_contents('logs/logs.txt', $output, FILE_APPEND | LOCK_EX);
      } catch(Exception $e) {

      }
    }

    // if ($data['result'] == 'success' && IS_SEND_MAIL_SENDER == true && false) {
    //   try {
    //     // очистка всех адресов и прикреплёных файлов
    //     $mail->clearAllRecipients();
    //     $mail->clearAttachments();
    //     // получаем содержимое email шаблона
    //     $bodyMail = file_get_contents('email_client.tpl'); 
    //     // выполняем замену плейсхолдеров реальными значениями
    //     $bodyMail = str_replace('%email.title%', MAIL_SUBJECT, $bodyMail);
    //     $bodyMail = str_replace('%email.nameuser%', isset($name) ? $name : '-', $bodyMail);
    //     $bodyMail = str_replace('%email.date%', date('d.m.Y H:i'), $bodyMail);
    //     // устанавливаем параметры
    //     $mail->Subject = MAIL_SUBJECT_CLIENT;
    //     $mail->Body = $bodyMail;
    //     $mail->addAddress($email);
    //     // отправляем письмо
    //     $mail->send();
    //   } catch(Exception $e) {

    //   }
    // }

    

 } else {
    $error[] = 'Нет настроек формы с именем #'.$act;
 }


$data['error'] = $error;

/* ФИНАЛЬНЫЙ ЭТАП - ВОЗВРАЩАЕМ РЕЗУЛЬТАТЫ РАБОТЫ */
echo json_encode($data);

/*
 * парсер шаблона
 */
 function tpl($vars) {
    $tpl = 'tpl/'.$vars['name'].'.tpl';
    if(file_exists($tpl)) {
     $template = file_get_contents($tpl);
        foreach($vars['getdata'] as $name => $value) {
            $template = str_replace(array("%".$name.".title%", "%".$name.".value%"), array($value['title'], $value['value']), $template);
        }
        $template = str_replace('%config.title%', $vars['config']['title'], $template);
        $template = str_replace('%config.date%', date('d.m.Y H:i'), $template);

        return $template;
    }
     else {
      return false;
    }
 }
