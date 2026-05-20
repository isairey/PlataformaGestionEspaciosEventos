<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    // Replace with your actual Gmail address
    public string $fromEmail = 'cspcsphere@gmail.com'; // ← YOUR GMAIL HERE
    public string $fromName = 'CSPC Booking System';
    public string $recipients = '';

    public string $userAgent = 'CodeIgniter';
    public string $protocol = 'smtp';
    public string $mailPath = '/usr/sbin/sendmail';
    
    // Gmail SMTP settings
    public string $SMTPHost = 'smtp.gmail.com';
    public string $SMTPUser = 'cspcsphere@gmail.com'; // ← YOUR GMAIL HERE (same as fromEmail)
    public string $SMTPPass = 'upaf ycxj tdqa huho'; // ← YOUR 16-CHAR APP PASSWORD HERE
    public int $SMTPPort = 587;
    public int $SMTPTimeout = 60;
    public bool $SMTPKeepAlive = false;
    public string $SMTPCrypto = 'tls';
    
    // Other settings
    public bool $wordWrap = true;
    public int $wrapChars = 76;
    public string $mailType = 'html';
    public string $charset = 'UTF-8';
    public bool $validate = true;
    public int $priority = 3;
    public string $CRLF = "\r\n";
    public string $newline = "\r\n";
    public bool $BCCBatchMode = false;
    public int $BCCBatchSize = 200;
    public bool $DSN = false;
}