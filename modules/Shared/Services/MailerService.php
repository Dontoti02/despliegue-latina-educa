<?php

namespace Modules\Shared\Services;

use App\Mail\NotificationMailable;
use Exception;

use Illuminate\Support\Facades\Mail;

class MailerService
{
  public static function send(object $data)
  {
    try {
      $subject = $data->subject;
      $body = $data->body;
      $to = $data->to;
      $cc = $data->cc ?? [];

      $template = new NotificationMailable($subject, $body);
      $template->from('notificaciones@latinaeduca.com', 'Notificaciones Latina Educa');

      Mail::to($to)->cc($cc)->queue($template);
    } catch (Exception $e) {
      logger('Error al enviar el correo: ' . $e->getMessage());
    }
  }
}
