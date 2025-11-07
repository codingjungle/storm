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
use IPS\IPS;
use IPS\Log;
use IPS\storm\Application;
use IPS\storm\DevToys\Dates;
use IPS\storm\Form;
use IPS\storm\DevToys\Lorem;
use IPS\storm\DevToys\Numbers;
use IPS\storm\Profiler\Debug;
use IPS\storm\Tpl;
use IPS\storm\DevToys\Uuid;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\storm\Head;
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

\IPS\storm\Application::initAutoloader();

/**
 * toys
 */
class toys extends Controller
{
    public function execute(): void
    {
        Head::i()->js(['global_copy']);
        parent::execute();
    }

    public function dates()
    {
        Head::i()->js(['global_dates']);
        $time = Request::i()->time ?? Date::create()->getTimestamp();
        $type = Request::i()->type ?? 'unix';
        $dates = Dates::i()->{$type}($time);
        if (isset(Request::i()->time)) {
            Output::i()->json($dates);
        } else {
            Output::i()->output = Tpl::get('toys.storm.global')->dates($dates);
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

        Head::i()->js(['global_diffs']);
        Output::i()->output = Tpl::get('toys.storm.global')->diffs();
    }

    protected function lorem(): void
    {
        Head::i()->js(['global_lorem']);

        $form = Form::create()->setPrefix('storm_devtoys_lorem_')->submitLang(null);
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
        Output::i()->output = Tpl::get('toys.storm.global')->lorem(
            $form,
            Lorem::i()->paragraphs(4, ['p'])
        );
    }

    protected function bitwiseValues()
    {
        Head::i()->js(['global_bitwise']);

        $position = Request::i()->position ?? 15;
        $bits = [];
        $pos = 15;
        for ($i = 1; $i <= $position; $i++) {
            $start = pow(2, $i - 1);
            if ($i === 1) {
                $bits[] = '<div>';
            }
            if (( $i - 1 ) % 15 === 0) {
                $bits[] = '</div><div>';
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
        Output::i()->output = Tpl::get('toys.storm.global')->bitwise(
            $pos,
            $bits
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
            $words[] = $hundreds . $tens . $singles . (($levels && (int)($num_levels[$i])) ? ' ' . $list3[$levels] . ' ' : '');
        } //end for loop
        $commas = count($words);
        if ($commas > 1) {
            $commas = $commas - 1;
        }
        return trim(implode(' ', $words));
    }

    protected function hash()
    {
        Head::i()->js(['global_hash']);
        $hash = Request::i()->hash ?? 'Hello World';
        $md5 = md5($hash);
        $sha1 = sha1($hash);
        $sha256 = hash('sha256', $hash);
        $sha512 = hash('sha512', $hash);
        Output::i()->output = Tpl::get('toys.storm.global')->hash(
            $hash,
            $md5,
            $sha1,
            $sha256,
            $sha512
        );
    }

    protected function uuid()
    {
        Head::i()->js(['global_uuid']);
        $count = Request::i()->count ?? 3;
        $hyphens = Request::i()->hyphens ?? true;
        $lowercase = Request::i()->lowercase ?? false;
        $html = [];

        $form = Form::create()->setPrefix('storm_devtoys_uuid_')->submitLang(null);
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
            Output::i()->output = Tpl::get('toys.storm.global')->uuid(
                $form,
                implode('<br>', $html)
            );
        } else {
            Output::i()->output = '<br>' . implode('<br>', $html);
        }
    }

    protected function html()
    {
        Head::i()->js(['global_html']);
        $encoded = $decoded = '<a href="#foo">link</a>';
        Output::i()->output = Tpl::get('toys.storm.global')->html($decoded, $encoded);
    }

    protected function base()
    {
        Head::i()->js(['global_base']);
        Output::i()->output = Tpl::get('toys.storm.global')->base();
    }

    protected function numbers()
    {
        Head::i()->js(['global_numbers']);
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
            Output::i()->output = Tpl::get('toys.storm.global')->numbers($output);
        }
    }

    protected function images()
    {
        Head::i()->js(['global_images']);
        $form = Form::create()->setPrefix('storm_devtoys_images_')->submitLang(null);
        $options = [
            'storageExtension' => 'storm_ImageConverter',
            'storageContainer' => 'devToysImageConverter',
            'allowedFileTypes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'],
        ];
        $form->addElement('images', 'upload')->options($options);
        $options = [
            'Jpeg' => 'jpg',
            'Png' => 'png',
            'Gif' => 'gif',
            'Tiff' => 'tif',
            'Bmp' => 'bmp',
            'Webp' => 'webp'
        ];
        ksort($options);
        $form->addElement('to', 'select')->options(['options' => $options])->value('Webp');

        if ($values = $form->values()) {
                $driver = Settings::i()->image_suite === 'imagemagick' &&
                class_exists(
                    'Imagick',
                    false
                ) ? \Intervention\Image\Drivers\Imagick\Driver::class : \Intervention\Image\Drivers\Gd\Driver::class;
                $manager = new ImageManager($driver);
                /** @var File $ogfile */
                $ogfile = $values['images'];
                $file = \IPS\ROOT_PATH . '/Uploads/';
                $file .= (string) $values['images'];
                $class = '\\Intervention\\Image\\Encoders\\' . $values['to'] . 'Encoder';
                $img = (string)$manager->read($file)->encode(new $class());
                $ext = mb_strtolower($values['to']);
                $newFile = File::create(
                    'storm_ImageConverter',
                    'imageConverted-' . $ext . '-' . uniqid() . '.' . $ext,
                    $img,
                    'devToysImageConverter',
                    true
                );
                $ogfile->delete();
                $newFile->save();
                Output::i()->json(['path' => (string)$newFile, 'url' => $newFile->url]);
        }

        $form->dialogForm();
        Output::i()->output = Tpl::get('toys.storm.global')->images($form);
    }

    protected function delete()
    {
        $path = Request::i()->path;
        $info = pathinfo($path);
        $file = File::get('storm_devtoys_ImageConverter', $path);
        $file->delete();
    }

    protected function pretty()
    {
        Head::i()->js(['global_pretty']);
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

        Output::i()->output = Tpl::get('toys.storm.global')->pretty($json, $pretty);
    }

    protected function download()
    {
        $path = (string) Request::i()->path;
        $t = (string) Request::i()->t;
        $info = pathinfo($path);
        $file = File::get($t === 'qr' ? 'storm_Qrcode' : 'storm_ImageConverter', $path);
        $contents = $file->contents(true);
        $name = $file->originalFilename;
        $file->delete();
        Output::i()->sendOutput(
            $contents,
            200,
            'image/' . $info['extension'],
            [
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
            Head::i()->js(['global_aes']);
            $decodedContent = 'Oh Hello Mark!';
            $encodedKey = $decodedKey = '8ad39920ad88';
            $encodedContent = AesCtr::encrypt($decodedContent, $decodedKey, 256);

            Output::i()->output = Tpl::get('toys.storm.global')->aes(
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
        $formCreate->setPrefix('storm_devtoys_qr_create_')->submitLang('storm_devtoys_qr_create_generate');
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
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'url' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });

        //text
        $formCreate->addElement('text', 'textarea')
            ->options(['maxLength' => 800])
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'text' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });

        //call
        $formCreate->addElement('phone2', 'tel')->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'call' && \mb_strlen($value) < 1) {
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
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'wifi' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('pass')
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'wifi' && Request::i()->storm_devtoys_qr_create_wifi !== 'nopass' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('hidden', 'yn');

        //contact
        $formCreate->addElement('name')
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'contact' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('surname')
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'contact' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('org');
        $formCreate->addElement('title');
        $formCreate->addElement('telw', 'tel')
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'contact' && \mb_strlen($value) < 1) {
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
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'email' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('subject')
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'email' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('body2', 'textarea')
            ->options(['maxLength' => 800])
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'email' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('address', 'addy');
        $formCreate->addElement('note', 'textarea')
            ->options(['maxLength' => 800]);

        //sms call
        $formCreate->addElement('phone', 'tel')
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'sms' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });
        $formCreate->addElement('body', 'textarea')
            ->options(['maxLength' => 800])
            ->appearRequired()
            ->validation(static function ($value) {
                if (Request::i()->storm_devtoys_qr_create_dataType === 'sms' && \mb_strlen($value) < 1) {
                    throw new \InvalidArgumentException('form_required');
                }
            });

        $formCreate->addTab('fg_bg_color');
        $formCreate->addMessage('color_warning', 'ipsMessage ipsMessage_error');
        if (Settings::i()->image_suite == 'imagemagick' && class_exists('Imagick', false)) {
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

        if (Settings::i()->image_suite == 'imagemagick' && class_exists('Imagick', false)) {
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
        if (Settings::i()->image_suite == 'imagemagick' && class_exists('Imagick', false)) {
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
            IPS::$PSR0Namespaces['BaconQrCode'] = \IPS\ROOT_PATH . '/system/3rd_party/BaconQrCode/src';
            IPS::$PSR0Namespaces['DASPRiD'] = \IPS\ROOT_PATH . '/system/3rd_party/DASPRiD';

            if (isset(Request::i()->previousQR)) {
                try {
                    //devToysForms
                    $file = File::get('storm_Qrcode', (string) Request::i()->previousQR);
                    $file->delete();
                } catch (\Throwable) {
                }
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
            } else {
                [$r, $g, $b] = sscanf($values['topLeft'], "#%02x%02x%02x");
                $outer = $inner = new Rgb($r, $g, $b);
                if ((bool)$values['eyeSeparateColorsInner']) {
                    [$r, $g, $b] = sscanf($values['topLeftInner'], "#%02x%02x%02x");
                    $inner = new Rgb($r, $g, $b);
                }
                $topLeft = new EyeFill($outer, $inner);

                [$r, $g, $b] = sscanf($values['topRight'], "#%02x%02x%02x");
                $outer = $inner = new Rgb($r, $g, $b);
                if ((bool)$values['eyeSeparateColorsInner']) {
                    [$r, $g, $b] = sscanf($values['topRightInner'], "#%02x%02x%02x");
                    $inner = new Rgb($r, $g, $b);
                }
                $topRight = new EyeFill($outer, $inner);

                [$r, $g, $b] = sscanf($values['bottomLeft'], "#%02x%02x%02x");
                $outer = $inner = new Rgb($r, $g, $b);
                if ((bool)$values['eyeSeparateColorsInner']) {
                    [$r, $g, $b] = sscanf($values['bottomLeftInner'], "#%02x%02x%02x");
                    $inner = new Rgb($r, $g, $b);
                }
                $bottomLeft = new EyeFill($outer, $inner);
            }

            $fill = Fill::withForegroundColor($bg, $fg, $topLeft, $topRight, $bottomLeft);

            if (Settings::i()->image_suite == 'imagemagick' && class_exists('Imagick', false)) {
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

                switch ((int)$values['module']) {
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
                        $size,
                        $margin,
                        $module,
                        $eye,
                        $fill
                    ),
                    $backend
                );
            } else {
                $renderer = new GDLibRenderer(
                    $size,
                    $margin,
                    'png',
                    9,
                    Fill::withForegroundColor(
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
                        $addy1 = implode(' ', $addy->addressLines) . ';' . $addy->city . ';' . $addy->region . ';' . $addy->postalCode . ';' . $ct;
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
            $file = File::create('storm_Qrcode', $randomFileName . '.' . $ext, $content, 'devToysQrCodes', true);
            $before = 'data:image/svg+xml;utf8,';
            $cont = $file->contents();
            if ($ext === 'png') {
                $before = 'data:image/jpeg;base64,';
                $cont = base64_encode($cont);
            } else {
                $cont = rawurlencode($cont);
            }
            $image = $before . $cont;
            $path = (string) $file;
        }

        if ($image !== null) {
            $formCreate->addHidden('previousQR', $path);
        }

        Output::i()->output = Tpl::get('toys.storm.global')->qr($formCreate, $image, $path);
    }

    public function qrdecode()
    {
        $decoded = null;
        $formDecode = Form::create(null, 'formDecode');
        $formDecode->ajaxOutput = true;
        $formDecode->setPrefix('storm_devtoys_qr_decode_')->submitLang('storm_devtoys_qr_decode_decode');
        $options = [
            'storageExtension' => 'storm_Qrcode',
            'storageContainer' => 'StormDevToysQrCodes',
            'allowedFileTypes' => ['png','jpg','jpeg','gif'],
        ];

        $formDecode->addElement('decodeQr', 'upload')->options($options);
        if ($values = $formDecode->values()) {
            try {
                IPS::$PSR0Namespaces['BaconQrCode'] = \IPS\ROOT_PATH . '/system/3rd_party/BaconQrCode/src';
                IPS::$PSR0Namespaces['DASPRiD'] = \IPS\ROOT_PATH . '/system/3rd_party/DASPRiD';
                /** @var File $file */
                $file = $values['decodeQr'];
                $contents = $file->contents();
                $qrcode = new QrReader(
                    $contents,
                    QrReader::SOURCE_TYPE_BLOB,
                    Settings::i()->image_suite == 'imagemagick' && class_exists('Imagick', false)
                );
                $decoded = $qrcode->text();
            } catch (\Throwable $e) {
            }
        }

        Output::i()->output = Tpl::get('toys.storm.global')->qrdecode($formDecode, $decoded);
    }
}
