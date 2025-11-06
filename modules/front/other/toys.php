<?php


namespace IPS\storm\modules\front\other;

/* To prevent PHP errors (extending class does not exist) revealing path */

use AesCtr;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Eye\PointyEye;
use BaconQrCode\Renderer\Eye\SimpleCircleEye;
use BaconQrCode\Renderer\Eye\SquareEye;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\DotsModule;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use BaconQrCode\Renderer\RendererStyle\GradientType;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use IPS\calendar\Date;
use IPS\devtoys\Application;
use IPS\devtoys\Dates;
use IPS\devtoys\Form;
use IPS\devtoys\Lorem;
use IPS\devtoys\Numbers;
use IPS\devtoys\Uuid;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use NumberFormatter;
use Zxing\QrReader;

use function base64_encode;
use function class_exists;
use function count;
use function defined;
use function hash;
use function header;
use function implode;
use function json_encode;
use function ksort;
use function mb_strtolower;
use function mb_strtoupper;
use function md5;
use function microtime;
use function pathinfo;
use function pow;
use function sha1;
use function sscanf;
use function str_replace;
use function str_split;
use function strip_tags;
use function strlen;
use function substr;
use function trim;
use function uniqid;

use const false;
use const JSON_PRETTY_PRINT;
use const null;
use const true;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * toys
 */
class toys extends Controller
{

    public function dates()
    {
        Application::addJs(['front_dates']);
        $time = Request::i()->time ?? Date::create()->getTimestamp();
        $type = Request::i()->type ?? 'unix';
        $dates = Dates::i()->{$type}($time);
        if (isset(Request::i()->time)) {
            Output::i()->json($dates);
        } else {
            Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->dates($dates);
        }
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        // This is the default method if no 'do' parameter is specified
    }

    protected function diffs(): void
    {
        Application::addJs(['front_diffs']);
        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->diffs();
    }

    protected function lorem(): void
    {
        Application::addJs(['front_lorem']);

        $form = Form::create()->setPrefix('devtoys_lorem_')->submitLang(null);
        $form->addElement('amount', 'number')->value(4)->options(['min' => 1]);
        $form->addElement('type', 'radio')->value(3)->options(
                [
                    'options' => [
                        1 => 'Words',
                        2 => 'Sentences',
                        3 => 'Paragraphs',
                    ],
                ]
            )->required();

        if ($values = $form->values()) {
            $return = '';
            $amount = $values['amount'];
            switch ($values['type']) {
                case 1:
                    $return = Lorem::i()->words($amount);
                    break;
                case 2:
                    $return = Lorem::i()->sentences($amount, ['p']);
                    break;
                case 3:
                    $return = Lorem::i()->paragraphs($amount, ['p']);
                    break;
            }

            Output::i()->json(['html' => $return, 'type' => 'toolboxClipBoard']);
        }
        $form->dialogForm();
        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->lorem(
            $form,
            Lorem::i()->paragraphs(4, ['p'])
        );
    }

    protected function bitwiseValues()
    {
        Application::addJs(['front_bitwise']);

        $position = Request::i()->position ?? 15;
        $bits = [];
        $pos = 15;
        $class = [];
        for ($i = 1; $i <= $position; $i++) {
            $start = pow(2, $i - 1);
            if (($i - 1) % 15 === 0) {
                $bits[] = '</div><div class="ipsPos_left ipsMargin_right">';
            }
            $nn = $i;
            if (class_exists('NumberFormatter')) {
                $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                $nn = $f->format($i);
            } else {
                $nn = $this->convertNumberToWord($i);
            }
            $bits [] = '<div>\'' . $nn . '\' => ' . $start . ',</div>';
        }

        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->bitwise(
            $pos,
            $bits,
            $class
        );
    }

    public function convertNumberToWord($num = false)
    {
        $num = str_replace([',', ' '], '', trim($num));
        if (!$num) {
            return false;
        }
        $num = (int)$num;
        $words = [];
        $list1 = [
            '',
            'one',
            'two',
            'three',
            'four',
            'five',
            'six',
            'seven',
            'eight',
            'nine',
            'ten',
            'eleven',
            'twelve',
            'thirteen',
            'fourteen',
            'fifteen',
            'sixteen',
            'seventeen',
            'eighteen',
            'nineteen'
        ];
        $list2 = ['', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred'];
        $list3 = [
            '',
            'thousand',
            'million',
            'billion',
            'trillion',
            'quadrillion',
            'quintillion',
            'sextillion',
            'septillion',
            'octillion',
            'nonillion',
            'decillion',
            'undecillion',
            'duodecillion',
            'tredecillion',
            'quattuordecillion',
            'quindecillion',
            'sexdecillion',
            'septendecillion',
            'octodecillion',
            'novemdecillion',
            'vigintillion'
        ];
        $num_length = strlen($num);
        $levels = (int)(($num_length + 2) / 3);
        $max_length = $levels * 3;
        $num = substr('00' . $num, -$max_length);
        $num_levels = str_split($num, 3);
        for ($i = 0; $i < count($num_levels); $i++) {
            $levels--;
            $hundreds = (int)($num_levels[$i] / 100);
            $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
            $tens = (int)($num_levels[$i] % 100);
            $singles = '';
            if ($tens < 20) {
                $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
            } else {
                $tens = (int)($tens / 10);
                $tens = ' ' . $list2[$tens] . ' ';
                $singles = (int)($num_levels[$i] % 10);
                $singles = ' ' . $list1[$singles] . ' ';
            }
            $words[] = $hundreds . $tens . $singles . (($levels && ( int )($num_levels[$i])) ? ' ' . $list3[$levels] . ' ' : '');
        } //end for loop
        $commas = count($words);
        if ($commas > 1) {
            $commas = $commas - 1;
        }
        return trim(implode(' ', $words));
    }

    protected function hash()
    {
        Application::addJs(['front_hash']);
        $hash = Request::i()->hash ?? 'Hello World';
        $md5 = md5($hash);
        $sha1 = sha1($hash);
        $sha256 = hash('sha256', $hash);
        $sha512 = hash('sha512', $hash);
        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->hash(
            $hash,
            $md5,
            $sha1,
            $sha256,
            $sha512
        );
    }

    protected function uuid()
    {
        Application::addJs(['front_uuid']);
        $count = Request::i()->count ?? 3;
        $hyphens = Request::i()->hyphens ?? true;
        $lowercase = Request::i()->lowercase ?? false;
        $html = [];

        $form = Form::create()->setPrefix('devtoys_uuid_')->submitLang(null);
        $form->addElement('count', 'number')->value(3)->options(['min' => 1]);
        $form->addElement('hyphens', 'yn')->value(1);
        $form->addElement('lowercase', 'yn');
        if ($values = $form->values()) {
            $form = '';
            $count = (int)$values['count'];
            $hyphens = (bool)$values['hyphens'];
            $lowercase = (bool)$values['lowercase'];
        }

        for ($i = 1; $i <= $count; $i++) {
            $hash = Uuid::v4();
            if ($hyphens === false) {
                $hash = str_replace('-', '', $hash);
            }
            if ($lowercase === true) {
                $hash = mb_strtolower($hash);
            } else {
                $hash = mb_strtoupper($hash);
            }
            $html[] = $hash;
        }

        if ($form instanceof Form) {
            Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->uuid(
                $form,
                implode('<br>', $html)
            );
        } else {
            Output::i()->output = '<br>' . implode('<br>', $html);
        }
    }

    protected function html()
    {
        Application::addJs(['front_html']);
        $encoded = $decoded = '<a href="#foo">link</a>';
        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->html($decoded, $encoded);
    }

    protected function base()
    {
        Application::addJs(['front_base']);
        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->base();
    }

    protected function numbers()
    {
        Application::addJs(['front_numbers']);
        $number = Request::i()->number ?? 3456;
        $type = Request::i()->type ?? 'decimal';
        try {
            $output = Numbers::i()->{$type}($number);
        } catch (InvalidArgumentException $e) {
            $output = [
                $type => $number,
                'error' => $e->getMessage()
            ];
        }
        if (isset(Request::i()->type)) {
            Output::i()->json($output);
        } else {
            Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->numbers($output);
        }
    }

    protected function images()
    {
        Application::addJs(['front_images']);
        $form = Form::create()->setPrefix('devtoys_images_')->submitLang(null);
        $options = [
            'storageExtension' => 'devtoys_ImageConverter',
            'storageContainer' => 'devToysImageConverter',
            'allowedFileTypes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'],
        ];
        $form->addElement('images', 'upload')->options($options)->required();
        $options = [
            'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'tif' => 'tif',
            'bmp' => 'bmp',
            'ico' => 'ico',
            'psd' => 'psd',
            'webp' => 'webp'
        ];
        ksort($options);
        $form->addElement('to', 'select')->options(['options' => $options])->value('png');

        if ($values = $form->values()) {
            Application::loadAutoLoader();
            $config = [
                'driver' => Settings::i()->image_suite == 'imagemagick' && class_exists( 'Imagick', false ) ? 'imagick' : 'gd'
            ];
            $manager = new ImageManager($config);
            /** @var File $file */
            $file = $values['images'];
            $img = (string)$manager->make($file->url)->encode($values['to']);

            $newFile = File::create(
                'devtoys_ImageConverter',
                'imageConverted-' . $values['to'] . '-' . uniqid() . '.' . $values['to'],
                $img,
                'devToysImageConverter',
                true
            );
            $file->delete();
            $newFile->save();
            Output::i()->json(['path' => (string)$newFile, 'url' => $newFile->url]);
        }

        $form->dialogForm();
        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->images($form);
    }

    protected function delete()
    {
        $path = Request::i()->path;
        $info = pathinfo($path);
        $file = File::get('devtoys_ImageConverter', $path);
        $file->delete();
    }

    protected function pretty()
    {
        Application::addJs(['front_pretty']);
        $arr = [
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten'
        ];
        $arr2 = [
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten'
        ];
        $json = json_encode($arr);
        $pretty = json_encode($arr2, JSON_PRETTY_PRINT);

        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->pretty($json, $pretty);
    }

    protected function download()
    {
        $path = (string) Request::i()->path;
        $t = (string) Request::i()->t;
        $info = pathinfo($path);
        $file = File::get($t === 'qr' ? 'devtoys_Qrcode' : 'devtoys_ImageConverter', $path);
        $contents = $file->contents(true);
        $name = $file->originalFilename;
        $file->delete();
        Output::i()->sendOutput(
            $contents, 200, 'image/' . $info['extension'], [
                'Content-Disposition' => Output::getContentDisposition(
                    'attachment',
                    $name
                ),
            ]
        );
    }

    protected function aes()
    {
        require_once Application::getRootPath() . '/system/3rd_party/AES/AES.php';

        if (Request::i()->ec === null) {
            Application::addJs(['front_aes']);
            $decodedContent = 'Oh Hello Mark!';
            $encodedKey = $decodedKey = '8ad39920ad88';
            $encodedContent = AesCtr::encrypt($decodedContent, $decodedKey, 256);
            Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->aes(
                $decodedContent,
                $decodedKey,
                $encodedContent,
                $encodedKey
            );
        } else {
            $ec = (string)Request::i()->ec;
            $dc = Request::i()->content;
            $bits = (int)Request::i()->bits;
            $key = Request::i()->key;
            $output = [];
            if ($ec === 'decode') {
                $output = [
                    'ec' => 'decode',
                    'content' => AesCtr::decrypt($dc, $key, $bits),
                    'bits' => $bits,
                    'key' => $key
                ];
            } elseif ($ec === 'encode') {
                $output = [
                    'ec' => 'encode',
                    'content' => AesCtr::encrypt($dc, $key, $bits),
                    'bits' => $bits,
                    'key' => $key
                ];
            }
            Output::i()->json($output);
        }
    }

    protected function qr()
    {
        $cleanse = function ($text) {
            $data = mb_str_split($text);
            $newText = '';
            foreach ($data as $value) {
                switch ($value) {
                    case ';':
                    case ':':
                    case '\\':
                        $newText .= '\\' . $value;
                        break;
                    default:
                        $newText .= $value;
                        break;
                }
            }

            return $newText;
        };
        $image = null;
        $ext = 'png';
        $path = null;
        $formCreate = Form::create(null, 'formCreate');
        $formCreate->ajaxOutput = true;
        $formCreate->setPrefix('devtoys_qr_create_')->submitLang('devtoys_qr_create_generate');
        $formCreate->addTab('general');
        $formCreate->addElement('dataType', 'select')->options([
                'options' => [
                    'url' => 'URL',
                    'contact' => 'Contact',
                    'wifi' => 'Wifi',
                    'text' => 'Text',
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'call' => 'Call'
                ]
            ])->toggles([
                'url' => ['url2'],
                'contact' => [
                    'name',
                    'surname',
                    'org',
                    'title',
                    'telw',
                    'telh',
                    'fax',
                    'url',
                    'email',
                    'address',
                    'note'
                ],
                'wifi' => ['wifi', 'ssid', 'pass', 'hidden'],
                'text' => ['text'],
                'email' => ['email2', 'subject', 'body2'],
                'sms' => ['phone', 'body'],
                'call' => ['phone2']
            ]);

        //url
        $formCreate->addElement('url2', 'url')
            ->options(['placeholder' => 'https://example.com'])
            ->appearRequired()
            ->validation(static function($value){
            if(Request::i()->devtoys_qr_create_dataType === 'url' && \mb_strlen($value) < 1){
                throw new \InvalidArgumentException('form_required');
            }
        });

        //text
        $formCreate->addElement('text', 'textarea')
            ->options(['maxLength' => 800])
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'text' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });

        //call
        $formCreate->addElement('phone2', 'tel')->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'call' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });

        //wifi
        $formCreate->addElement('wifi', 'select')
            ->options([
                'options' => [
                    'wep' => 'WEP',
                    'wpa' => 'WPA',
                    'wpa2' => 'WPA2',
                    'nopass' => 'No encryption'
                ]
            ])
            ->toggles([
                'WEP' => ['pass'],
                'WPA' => ['pass'],
                'WPA2' => ['pass']
            ]);
        $formCreate->addElement('ssid')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'wifi' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('pass')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'wifi' && Request::i()->devtoys_qr_create_wifi !== 'nopass' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('hidden', 'yn');

        //contact
        $formCreate->addElement('name')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'contact' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('surname')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'contact' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('org');
        $formCreate->addElement('title');
        $formCreate->addElement('telw', 'tel')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'contact' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('telh', 'tel');
        $formCreate->addElement('fax', 'tel');
        $formCreate->addElement('url', 'url')
            ->options(['placeholder' => 'https://example.com']);
        $formCreate->addElement('email', 'email');
        $formCreate->addElement('email2', 'email')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'email' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('subject')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'email' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('body2', 'textarea')
            ->options(['maxLength' => 800])
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'email' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('address', 'addy');
        $formCreate->addElement('note', 'textarea')
            ->options(['maxLength' => 800]);

        //sms call
        $formCreate->addElement('phone', 'tel')
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'sms' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('body', 'textarea')
            ->options(['maxLength' => 800])
            ->appearRequired()
            ->validation(static function($value){
                if(Request::i()->devtoys_qr_create_dataType === 'sms' && \mb_strlen($value) < 1){
                    throw new \InvalidArgumentException('form_required');
                }
            });

        $formCreate->addTab('fg_bg_color');
        $formCreate->addMessage('color_warning', 'ipsMessage ipsMessage_error');
        if( Settings::i()->image_suite == 'imagemagick' && class_exists( 'Imagick', false ) ){
            $formCreate->addElement('fileType', 'select')
                ->options([
                    'options' => [
                        1 => 'PNG',
                        2 => 'SVG'
                    ]
                ]);
        }

        $formCreate->addElement('size', 'num')
            ->options([
                'min' => 50,
                'max' => 1920
            ])
            ->empty(400);
        $formCreate->addElement('margin', 'num')
            ->options([
                'min' => 1,
                'max' => 500
            ])
            ->empty(4);

        if( Settings::i()->image_suite == 'imagemagick' && class_exists( 'Imagick', false ) ){
            $formCreate->addElement('module', 'select')->options([
                'options' => [
                    1 => 'square',
                    2 => 'round',
                    3 => 'dots'
                ]
            ]);
            $formCreate->addElement('gradient', 'yn')
                ->toggles([
                    'gradientStart',
                        'gradientEnd',
                        'gradientType'
                ])
                ->toggles(['fg'], true);
            $formCreate->addElement('gradientType', 'select')
                ->options([
                    'options' => [
                        1 => 'Vertical',
                        2 => 'Horizontal',
                        3 => 'Diagonal',
                        4 => 'Inverse Diagonal',
                        5 => 'Radial'
                    ]
                ]);
            $formCreate->addElement('gradientStart', 'color')
                ->empty('#000000');
            $formCreate->addElement('gradientEnd', 'color')
                ->empty('#ffffff');
        }

        $formCreate->addElement('bg', 'color')
            ->empty('#ffffff');
        $formCreate->addElement('fg', 'color')
            ->empty('#000000');

        $formCreate->addTab('eye_color');

        $formCreate->addMessage('color_warning2', 'ipsMessage ipsMessage_error');
        if( Settings::i()->image_suite == 'imagemagick' && class_exists( 'Imagick', false ) ){
            $formCreate->addElement('eye', 'select')->options([
                'options' => [
                    1 => 'square',
                    2 => 'round',
                    3 => 'pointy']
            ]);
        }
        $formCreate->addElement('eyeSeparateColors', 'yn')
            ->toggles(['eyeUniform'], true)
            ->toggles([
                'topLeft',
                'topRight',
                'bottomLeft',
                'bottomRight',
                'eyeSeparateColorsInner'
            ]);
        $formCreate->addElement('eyeSeparateColorsInner', 'yn')
            ->toggles([
                'topLeftInner',
                'topRightInner',
                'bottomLeftInner',
                'bottomRightInner'
            ]);
        $formCreate->addElement('eyeUniform', 'color')
            ->empty('#000000');
        $formCreate->addElement('topLeft', 'color')
            ->empty('#000000');
        $formCreate->addElement('topLeftInner', 'color')
            ->empty('#000000');
        $formCreate->addElement('topRight', 'color')
            ->empty('#000000');
        $formCreate->addElement('topRightInner', 'color')
            ->empty('#000000');
        $formCreate->addElement('bottomLeft', 'color')
            ->empty('#000000');
        $formCreate->addElement('bottomLeftInner', 'color')
            ->empty('#000000');

        if ($values = $formCreate->values()) {
            Application::loadAutoLoader();
            if(isset(Request::i()->previousQR)){
                try {
                    //devToysForms
                    $file = File::get('devtoys_Qrcode', (string) Request::i()->previousQR);
                    $file->delete();
                }catch(\Throwable $e){}
            }
            $size = (int)$values['size'];
            $margin = (int)$values['margin'];
            [$fgr, $fgg, $fgb] = sscanf($values['fg'], "#%02x%02x%02x");
            $fg = new Rgb($fgr, $fgg, $fgb);
            [$bgr, $bgg, $bgb] = sscanf($values['bg'], "#%02x%02x%02x");
            $bg = new Rgb($bgr, $bgg, $bgb);
            if ((bool)$values['eyeSeparateColors'] === false) {
                [$r, $g, $b] = sscanf($values['eyeUniform'], "#%02x%02x%02x");
                $topLeft = $topRight = $bottomLeft = new EyeFill(new Rgb($r, $g, $b), new Rgb($r, $g, $b));
            }
            else {
                [$r, $g, $b] = sscanf($values['topLeft'], "#%02x%02x%02x");
                $outer = $inner = new Rgb($r, $g, $b);
                if ((bool)$values['eyeSeparateColorsInner']) {
                    [$r, $g, $b] = sscanf($values['topLeftInner'], "#%02x%02x%02x");
                    $inner = new Rgb($r, $g, $b);
                }
                $topLeft = new EyeFill($outer,$inner);

                [$r, $g, $b] = sscanf($values['topRight'], "#%02x%02x%02x");
                $outer = $inner = new Rgb($r, $g, $b);
                if ((bool)$values['eyeSeparateColorsInner']) {
                    [$r, $g, $b] = sscanf($values['topRightInner'], "#%02x%02x%02x");
                    $inner = new Rgb($r, $g, $b);
                }
                $topRight = new EyeFill($outer,$inner);

                [$r, $g, $b] = sscanf($values['bottomLeft'], "#%02x%02x%02x");
                $outer = $inner = new Rgb($r, $g, $b);
                if ((bool)$values['eyeSeparateColorsInner']) {
                    [$r, $g, $b] = sscanf($values['bottomLeftInner'], "#%02x%02x%02x");
                    $inner = new Rgb($r, $g, $b);
                }
                $bottomLeft = new EyeFill($outer,$inner);
            }

            $fill = Fill::withForegroundColor($bg, $fg, $topLeft, $topRight, $bottomLeft);

            if( Settings::i()->image_suite == 'imagemagick' && class_exists( 'Imagick', false ) ){
                switch ((int)$values['fileType']) {
                    default:
                    case 1:
                        $backend = new ImagickImageBackEnd();
                        break;
                    case 2:
                        $backend = new SvgImageBackEnd();
                        $ext = 'svg';
                        break;
                }

                switch ( (int)$values['module']) {
                    default:
                    case 1:
                        $module = SquareModule::instance();
                        break;
                    case 2:
                        $module = new RoundnessModule(1);
                        break;
                    case 3:
                        $module = new DotsModule(1);
                        break;
                }

                switch ((int)$values['eye']) {
                    default:
                    case 1:
                        $eye = SquareEye::instance();
                        break;
                    case 2:
                        $eye = SimpleCircleEye::instance();
                        break;
                    case 3:
                        $eye = PointyEye::instance();
                        break;
                }

                if ((bool)$values['gradient']) {
                    //                ->options(['options' => [1 => 'Vertical', 2 => 'Horizontal', 3 => 'Diagonal', 4 => 'Inverse Diagonal', 5 => 'Radial']]);
                    $gradientType = (int)$values['gradientType'];
                    switch ($gradientType) {
                        default:
                        case 1:
                            $radientType = GradientType::VERTICAL();
                            break;
                        case 2:
                            $radientType = GradientType::HORIZONTAL();
                            break;
                        case 3:
                            $radientType = GradientType::DIAGONAL();
                            break;
                        case 4:
                            $radientType = GradientType::INVERSE_DIAGONAL();
                            break;
                        case 5:
                            $radientType = GradientType::RADIAL();
                            break;
                    }

                    [$fgr, $fgg, $fgb] = sscanf($values['gradientStart'], "#%02x%02x%02x");
                    [$bgr, $bgg, $bgb] = sscanf($values['gradientEnd'], "#%02x%02x%02x");
                    $start = new Rgb($fgr, $fgg, $fgb);
                    $end = new Rgb($bgr, $bgg, $bgb);
                    $gradient = new Gradient($start, $end, $radientType);
                    $fill = Fill::withForegroundGradient($bg, $gradient, $topLeft, $topRight, $bottomLeft);
                }

                $renderer = new ImageRenderer(
                    new RendererStyle(
                        $size, $margin, $module, $eye, $fill
                    ), $backend
                );
            } else {
                $renderer = new GDLibRenderer(
                    $size, $margin, 'png', 9, Fill::withForegroundColor(
                    $fg,
                    $bg,
                    $topLeft,
                    $topRight,
                    $bottomLeft
                )
                );
            }

            switch ($values['dataType']) {
                case 'sms':
                    $body = strip_tags($values['body']);
                    $data = 'SMSTO:' . $values['phone'] . ':' . $body;
                    break;
                case 'call':
                    $data = 'TEL:' . $values['phone2'];
                    break;
                case 'url':
                    $data = $values['url2'];
                    break;
                case 'email':
                    $body = strip_tags($values['body2']);
                    $data = 'MATMSG:TO:' . $cleanse($values['email2']) . ';SUB:' . $cleanse(
                            $values['subject']
                        ) . ';BODY:' . $cleanse($body);
                    break;
                case 'contact':
                    $data = "BEGIN:VCARD\nVERSION:4.0\n";

                    if ($values['name']) {
                        $data .= "N:{$values['surname']};{$values['name']}\n";
                        $data .= "FN:{$values['name']} {$values['surname']}\n";
                    }

                    if ($values['org']) {
                        $data .= "ORG:{$values['org']}\n";
                    }

                    if ($values['title']) {
                        $data .= "TITLE:{$values['title']}\n";
                    }

                    if ($values['telw']) {
                        $data .= "TEL;TYPE=WORK:{$values['telw']}\n";
                    }

                    if ($values['telh']) {
                        $data .= "TEL;TYPE=HOME:{$values['telh']}\n";
                    }

                    if ($values['fax']) {
                        $data .= "TEL;TYPE=FAX:{$values['fax']}\n";
                    }

                    if ($values['url']) {
                        $data .= "URL:{$values['url']}\n";
                    }

                    if ($values['email']) {
                        $data .= "EMAIL:{$values['email']}\n";
                    }

                    if ($values['address']) {
                        $addy = $values['address'];
                        $ct = $addy->country === 'US' ? 'USA' : $addy->country;
                        $addy1 = implode(' ', $addy->addressLines).';'. $addy->city.';'.$addy->region.';'.$addy->postalCode.';'.$ct;
                        $data .= "ADR;TYPE=work:;;{$addy1}\n";
                    }

                    if ($values['note']) {
                        $note = strip_tags($values['note']);
                        $data .= "NOTE:{$note}\n";
                    }

                    $data .= 'END:VCARD';

                    break;
                case 'wifi':
                    $ssid = $cleanse($values['ssid']);
                    $pass = $cleanse($values['pass']);
                    $p = "P:{$pass};;";
                    $data = "WIFI:";
                    switch ($values['wifi']) {
                        case 'wep':
                            $data .= "T:WEP;";
                            break;
                        case 'wpa':
                            $data .= "T:WPA;";
                            break;
                        case 'wpa2':
                            $data .= "T:WPA2;";
                            break;
                        case 'nopass':
                            $data .= "T:nopass;";
                            $p = "P:;;";
                            break;
                    }
                    if ((bool)$values['hidden']) {
                        $data .= 'H:true;';
                    }
                    $data .= "S:{$ssid};";
                    $data .= $p;
                    break;
                case 'text':
                    $data = strip_tags($values['text']);
                    break;
                default:
                    $data = '';
                    break;
            }

            $writer = new Writer($renderer);
            $content = $writer->writeString($data);
            $randomFileName = sha1(time() . microtime());
            File::$safeFileExtensions = array_merge(['svg'], File::$safeFileExtensions);
            $file = File::create('devtoys_Qrcode', $randomFileName . '.' . $ext, $content, 'devToysQrCodes', true);
            $before = 'data:image/svg+xml;utf8,';
            $cont = $file->contents();
            if($ext === 'png'){
                $before = 'data:image/jpeg;base64,';
                $cont = base64_encode($cont);
            }
            else{
                $cont = rawurlencode($cont);
            }
            $image = $before.$cont;
            $path = (string) $file;
        }

        if($image !== null){
            $formCreate->addHidden('previousQR', $path);
        }


        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->qr($formCreate,$image,$path);
    }

    public function qrdecode(){
        $decoded = null;
        $formDecode = Form::create(null, 'formDecode');
        $formDecode->ajaxOutput = true;
        $formDecode->setPrefix('devtoys_qr_decode_')->submitLang('devtoys_qr_decode_decode');
        $options = [
            'storageExtension' => 'devtoys_Qrcode',
            'storageContainer' => 'devToysQrCodes',
            'allowedFileTypes' => ['png','jpg','jpeg','gif'],
        ];

        $formDecode->addElement('decodeQr', 'upload')->options($options);
        if($values = $formDecode->values()){
            try {
                Application::loadAutoLoader();
                /** @var File $file */
                $file = $values['decodeQr'];
                $contents = $file->contents();
                $qrcode = new QrReader(
                    $contents,
                    QrReader::SOURCE_TYPE_BLOB,
                    Settings::i()->image_suite == 'imagemagick' && class_exists('Imagick', false)
                );
                $decoded = $qrcode->text();
            }
            catch(\Throwable $e){}
        }

        Output::i()->output = Theme::i()->getTemplate('toys', 'devtoys', 'front')->qrdecode($formDecode, $decoded);

    }


}