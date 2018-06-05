<?php

/**
 * @name Ical.php
 * @version 1.0
 * @author Jonathon Hill
 *
 * This is a basic PHP class for building iCalendar files for events.
 * Thanks to http://bradym.net/php/creating-icalendar-ics-files-with-php for getting me started.
 */

class Ical  {

	public $filename = FALSE;
	public $events = array();
	public $body;

	public static $timezones = array(
		'-05:00' => 'America/New_York',
		'-06:00' => 'America/Chicago',
		'-07:00' => 'America/Phoenix',
		'-08:00' => 'America/Los_Angeles'
	);

	const HEADER =
"BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//Company52//Ical.php//EN\nMETHOD:REQUEST\n";
	
	const FOOTER = "END:VCALENDAR\n";

	function __construct($evtData = NULL, $mode = 'string', $filename = NULL) {

		foreach((array) $evtData as $data) {
			$this->add_event($data['uid'], $data['start'], $data['end'], $data['summary'], $data['description'], $data['tz']);
		}

		if(count($this->events)) return $this->build($mode, $filename);
	}

	function __destruct() {
		if($this->filename) {
			@unlink($this->filename);
		}
	}

	function add_event($uid, $start = NULL, $end = NULL, $summary = NULL, $description = NULL, $tz = NULL) {

		$this->events[] = (is_array($uid))?
			$uid :
			array(
				'uid' => $uid,
				'start' => $start,
				'end' => $end,
				'summary' => $summary,
				'description' => $description,
				'tz' => $tz,
			);
	}

	function build($mode = 'string', $filename = NULL) {

		# calendar file header
		$this->body = Ical::HEADER;
		$system_tz = date_default_timezone_get();

		foreach($this->events as $evt) {

			# open the event
			$this->body .= "BEGIN:VEVENT\n";


			# set the time zone
			$tz = (array_key_exists($evt['tz'], Ical::$timezones))? Ical::$timezones[$evt['tz']] : Ical::$timezones['-06:00'];
			date_default_timezone_set($tz);
			$tz = date_default_timezone_get();

			$evt['start'] = strtotime($evt['start']);


			# all day event?
			if(is_null($evt['end'])) {

				# all-day events end the next day
				$end = strtotime('+1 day', $evt['start']);

				$this->body .= strftime("DTSTART;VALUE=DATE:%Y%m%d\n", $evt['start']);
	   			$this->body .= strftime("DTEND;VALUE=DATE:%Y%m%d\n", $end);

			} else {

				if(is_array($evt['end'])) {
					$evt['end'] = strtotime($evt['end'][1], strtotime($evt['end'][0]));
				} else {
					$evt['end'] = strtotime($evt['end']);
				}

				# compute GMT start and end times
				$this->body .= gmstrftime("DTSTART:%Y%m%dT%H%M%SZ\n", $evt['start']);
	   			$this->body .= gmstrftime("DTEND:%Y%m%dT%H%M%SZ\n", $evt['end']);

			}

			# description given?
			if(strlen($evt['description'])) {

				# escape linebreaks, commas, and semicolons
            	$description = str_replace("\r", '', $evt['description']);
            	$description = str_replace("\n", '\n', $description);
            	$description = str_replace(',', '\,', $description);
            	$description = str_replace(';', '\;', $description);

           		$this->body .= wordwrap("DESCRIPTION:$description \n", 80, " \n ");
			}

			# remaining info, close the event
			$this->body .= "SUMMARY:{$evt['summary']}\n";
	    	$this->body .= "UID:{$evt['uid']}\n";
	   		$this->body .= "SEQUENCE:0\n";
	   		$this->body .= "DTSTAMP:".date('Ymd').'T'.date('His')."\n";
	   		$this->body .= "END:VEVENT\n";

		}

		date_default_timezone_set($system_tz);

		# calendar file footer
		$this->body .= Ical::FOOTER;


		switch($mode) {

			case 'download':
				header('Content-Type: text/calendar; charset=utf-8');
				header('Content-Length: '.strlen($this->body));
				header('Content-Disposition: attachment; filename=calendar.ics');
				echo $this->body;
				break;
				
			case 'inline':
				header('Content-Type: text/calendar; charset=utf-8');
				header('Content-Length: '.strlen($this->body));
				header('Content-Disposition: inline; filename=calendar.ics');
				echo $this->body;
				break;
			
			case 'file':
				if($filename === NULL) {
					$filename = TEMPDIR.'/ical'.rand().'.ics';
				}

				$fh = @fopen($filename, 'w');
				if($fh !== FALSE) {
					$this->filename = $filename;
					fwrite($fh, $this->body);
					fclose($fh);
					return $filename;
				} else {
					throw new ErrorException("Could not open $filename for writing", E_USER_WARNING);
				}
				break;
			
			case 'string':
			default:
				return $this->body;

		}

	}

	function email_meeting_request($to, $from, $subject, $textBody, $htmlBody = FALSE) {

		# MIME/multipart message boundary
		$mime_boundary = sprintf('----_=_NextPart_001_%s', uniqid());

		# message headers
		$headers = array();
		$headers[] = "Content-class: urn:content-classes:calendarmessage";
		$headers[] = "Date: ".date(DATE_RFC1123);
		$headers[] = "From: $from <$from>";
		$headers[] = "To: $to <$to>";
		$headers[] = "Subject: $subject";
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"";
		$headers[] = "";
		$headers[] = "This is a MIME message. If you are reading this text, you may want to ";
		$headers[] = "consider changing to a mail reader or gateway that understands how to ";
		$headers[] = "properly handle MIME multipart messages.";
		$headers[] = "";

		# plaintext body
		$body[] = "--".$mime_boundary;
		$body[] = "Content-Type: text/plain; charset=\"US-ASCII\"";
		$body[] = "Content-Transfer-Encoding: quoted-printable";
		$body[] = "";
		$body[] = $textBody;
		$body[] = "";

		# HTML body
		if($htmlBody !== FALSE) {
			$body[] = "--".$mime_boundary;
			$body[] = "Content-Type: text/html; charset=\"iso-8859-1\"";
			$body[] = "Content-Transfer-Encoding: 8bit";
			$body[] = "";
			$body[] = $htmlBody;
			$body[] = "";
		}


		# iCalendar inline attachment
		# These headers are very special. They make Outlook see this is a meeting request.
		$body[] = "--".$mime_boundary;
		$body[] = "Content-class: urn:content-classes:calendarmessage";
		$body[] = "Content-Type: text/calendar; method=REQUEST; name=meeting.ics; charset=UTF-8";
		$body[] = "Content-Transfer-Encoding: 8bit";
		$body[] = "";
		$body[] = $this->build();

		# Assemble body and headers
		$body[] = "--".$mime_boundary."--\n";
		$headers = implode("\n", $headers);
		$body = implode("\n", $body);

		#echo '<pre>'.$headers."\n\n".$body."\n\n";

		# off we go!
		return Mailer::save_email($to, $headers, $body);
	}


}
