<?php
/** ClamAV INSTREAM protocol: no shell command and no user-controlled scanner address. */
function scan_uploaded_content(string $path): void {
    $address = getenv('CLAMAV_ADDRESS') ?: '';
    if ($address === '') {
        if (getenv('UPLOAD_SCAN_REQUIRED') === '1' || (defined('APP_ENV') && APP_ENV === 'production')) {
            throw new RuntimeException('File scanning is unavailable. Please contact support.');
        }
        return; // Local development only. Production fails closed without a scanner.
    }
    $socket = @stream_socket_client($address, $errno, $error, 5);
    if (!$socket) throw new RuntimeException('File scanning is temporarily unavailable.');
    stream_set_timeout($socket, 30);
    $file = fopen($path, 'rb');
    try {
        $write = static function ($socket, string $data): void {
            while ($data !== '') {
                $written = fwrite($socket, $data);
                if (!$written) throw new RuntimeException('File scanning did not complete.');
                $data = substr($data, $written);
            }
        };
        if (!$file) throw new RuntimeException('Unable to read uploaded content.');
        $write($socket, "zINSTREAM\0");
        while (!feof($file)) {
            $chunk = fread($file, 8192);
            if ($chunk === false) throw new RuntimeException('Unable to read uploaded content.');
            if ($chunk !== '') $write($socket, pack('N', strlen($chunk)) . $chunk);
        }
        $write($socket, pack('N', 0));
        $reply = stream_get_line($socket, 4096, "\0");
        if ($reply !== 'stream: OK') throw new RuntimeException('The uploaded file could not pass the security scan.');
    } finally { if (is_resource($file)) fclose($file); fclose($socket); }
}
