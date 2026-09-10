<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mail;

class MailController extends Controller
{
    /**
     * Load Startpage accordingly to the given URL-Parameter and Mobile
     *
     * @param  int  $id
     * @return Response
     */
    public function contactMail(Request $request){

        # Message that we forward to the user:
        $messageType = ""; # [success|error]
        $returnMessage = '';
        $replyTo = $request->input('email', 'noreply@metager.de');
        if($replyTo === ""){
            $replyTo = "noreply@metager.de";
        }else{
            $replyTo = $request->input('email');
        }

        if(!$request->has('message')){
            $messageType = "error";
            $returnMessage = trans('mail.contact_no_data');
        }else{
            # We send the user's mail to us:
            $message = $request->input('message');
            $subject = "[Ticket " . date("Y") . date("d") . date("m") . date("H") . date("i") . date("s") . "] MetaGer - Kontaktanfrage";
            if( Mail::send(['text' => 'kontakt.mail'], ['messageText'=>$message], function($message) use($replyTo, $subject){
                $message->to("office@suma-ev.de", $name = null);
                $message->from($replyTo, $name = null);
                $message->replyTo($replyTo, $name = null);
                $message->subject($subject);
            }) ){
                # Mail successfully sent
                $messageType = "success";
                $returnMessage = trans('mail.contact_success');
            }else{
                # Error sending the email
                $messageType = "error";
                $returnMessage = trans('mail.contact_error');
            }

            $messageType = "success";
        }

    
        return view('kontakt.kontakt')
                ->with('title', 'Kontakt')
                ->with('css', 'kontakt.css')
                ->with('js', ['openpgp.min.js','kontakt.js'])
                ->with( $messageType, $returnMessage );
    }

    public function donation(Request $request)
    {
        # The contained string is shown to the user after the donation
        $messageToUser = "";
        $messageType = ""; # [success|error]

        # The following fields are passed as input from the donation form:
        # Name
        # Phone
        # email
        # Account number (IBAN)
        # Bank code (BIC)
        # Message
        if(!$request->has('Kontonummer') || !$request->has('Bankleitzahl') || !$request->has('Nachricht')){
            $messageToUser = trans('mail.donation_missing_fields');
            $messageType = "error";
        }else{
            $message = "\r\nName: " . $request->input('Name', trans('mail.no_value'));
            $message .= "\r\nTelefon: " . $request->input('Telefon', trans('mail.no_value'));
            $message .= "\r\nKontonummer: " . $request->input('Kontonummer');
            $message .= "\r\nBankleitzahl: " . $request->input('Bankleitzahl');
            $message .= "\r\nNachricht: " . $request->input('Nachricht');

            $replyTo = $request->input('email', 'anonymous-user@metager.de');
            if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $messageToUser .= trans('mail.donation_invalid_email', ['email' => $replyTo]);
            }

            try{
                if(Mail::send(['text' => 'kontakt.mail'], ['messageText'=>$message], function($message) use($replyTo){
                    $message->to("office@suma-ev.de", $name = null);
                    $message->from($replyTo, $name = null);
                    $message->replyTo($replyTo, $name = null);
                    $message->subject("MetaGer - Spende");
                })) {
                    $messageType = "success";
                    $messageToUser = trans('mail.donation_success');
                }else{
                    $messageType = "error";
                    $messageToUser = trans('mail.donation_error');
                }
            } catch( \Swift_TransportException $e ){
                $messageType = "error";
                $messageToUser = trans('mail.donation_error');
            }
        }


        return view('spende.spende')
                ->with('title', 'Kontakt')
                ->with('css', 'donation.css')
                ->with($messageType,$messageToUser);
    }
}