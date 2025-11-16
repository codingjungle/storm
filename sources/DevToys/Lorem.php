<?php

/**
 * @brief       Lorem Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox
 * @since       4.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\DevToys;

use IPS\Patterns\Singleton;

use function array_merge;
use function array_reverse;
use function array_slice;
use function cos;
use function count;
use function implode;
use function is_array;
use function log;
use function mb_ucfirst;
use function mt_getrandmax;
use function mt_rand;
use function round;
use function shuffle;
use function sprintf;
use function sqrt;
use function str_replace;

use const false;
use const M_PI;
use const true;

/**
 * Class _Lorem
 *
 * @package IPS\toolbox\Shared
 * @mixin Lorem
 */
class Lorem extends Singleton
{
    protected static ?Singleton $instance = null;

    /**
     * Words
     *
     * Lorem ipsum vocabulary of sorts. Not a complete list as I'm unsure if
     * a complete list exists and if so, where to get it.
     *
     * @access private
     * @var    array
     */
    public array $words = [
        // Lorem ipsum...
        'lorem',
        'ipsum',
        'dolor',
        'sit',
        'amet',
        'consectetur',
        'adipiscing',
        'elit',

        // and the rest of the vocabulary
        'a',
        'ac',
        'accumsan',
        'ad',
        'aenean',
        'aliquam',
        'aliquet',
        'ante',
        'aptent',
        'arcu',
        'at',
        'auctor',
        'augue',
        'bibendum',
        'blandit',
        'class',
        'commodo',
        'condimentum',
        'congue',
        'consequat',
        'conubia',
        'convallis',
        'cras',
        'cubilia',
        'curabitur',
        'curae',
        'cursus',
        'dapibus',
        'diam',
        'dictum',
        'dictumst',
        'dignissim',
        'dis',
        'donec',
        'dui',
        'duis',
        'efficitur',
        'egestas',
        'eget',
        'eleifend',
        'elementum',
        'enim',
        'erat',
        'eros',
        'est',
        'et',
        'etiam',
        'eu',
        'euismod',
        'ex',
        'facilisi',
        'facilisis',
        'fames',
        'faucibus',
        'felis',
        'fermentum',
        'feugiat',
        'finibus',
        'fringilla',
        'fusce',
        'gravida',
        'habitant',
        'habitasse',
        'hac',
        'hendrerit',
        'himenaeos',
        'iaculis',
        'id',
        'imperdiet',
        'in',
        'inceptos',
        'integer',
        'interdum',
        'justo',
        'lacinia',
        'lacus',
        'laoreet',
        'lectus',
        'leo',
        'libero',
        'ligula',
        'litora',
        'lobortis',
        'luctus',
        'maecenas',
        'magna',
        'magnis',
        'malesuada',
        'massa',
        'mattis',
        'mauris',
        'maximus',
        'metus',
        'mi',
        'molestie',
        'mollis',
        'montes',
        'morbi',
        'mus',
        'nam',
        'nascetur',
        'natoque',
        'nec',
        'neque',
        'netus',
        'nibh',
        'nisi',
        'nisl',
        'non',
        'nostra',
        'nulla',
        'nullam',
        'nunc',
        'odio',
        'orci',
        'ornare',
        'parturient',
        'pellentesque',
        'penatibus',
        'per',
        'pharetra',
        'phasellus',
        'placerat',
        'platea',
        'porta',
        'porttitor',
        'posuere',
        'potenti',
        'praesent',
        'pretium',
        'primis',
        'proin',
        'pulvinar',
        'purus',
        'quam',
        'quis',
        'quisque',
        'rhoncus',
        'ridiculus',
        'risus',
        'rutrum',
        'sagittis',
        'sapien',
        'scelerisque',
        'sed',
        'sem',
        'semper',
        'senectus',
        'sociosqu',
        'sodales',
        'sollicitudin',
        'suscipit',
        'suspendisse',
        'taciti',
        'tellus',
        'tempor',
        'tempus',
        'tincidunt',
        'torquent',
        'tortor',
        'tristique',
        'turpis',
        'ullamcorper',
        'ultrices',
        'ultricies',
        'urna',
        'ut',
        'varius',
        'vehicula',
        'vel',
        'velit',
        'venenatis',
        'vestibulum',
        'vitae',
        'vivamus',
        'viverra',
        'volutpat',
        'vulputate',
    ];

    /**
     * First
     *
     * Whether we should be starting the string with "Lorem ipsum..."
     *
     * @access private
     * @var    bool
     */
    protected bool $first = true;
    protected array $firstWords = [];

    /**
     * Word
     *
     * Generates a single word of lorem ipsum.
     *
     * @access public
     *
     * @param mixed $tags string or array of HTML tags to wrap output with
     *
     * @return string generated lorem ipsum word
     */
    public function word(array|string $tags = '')
    {
        return $this->words(1, $tags);
    }

    /**
     * Words
     *
     * Generates words of lorem ipsum.
     *
     * @access public
     *
     * @param int $count how many words to generate
     * @param mixed $tags string or array of HTML tags to wrap output with
     * @param bool $array whether an array or a string should be returned
     *
     * @return mixed   string or array of generated lorem ipsum words
     */
    public function words(int|float $count = 1, array|string $tags = '', bool $array = false)
    {
        $count = round($count);
        $words = [];
        $word_count = 0;

        // Shuffles and appends the word list to compensate for count
        // arguments that exceed the size of our vocabulary list
        while ($word_count < $count) {
            $shuffle = true;

            while ($shuffle) {
                $this->shuffle();

                // Checks that the last word of the list and the first word of
                // the list that's about to be appended are not the same
                if (
                    !$word_count ||
                    $words[$word_count - 1] !== $this->words[0]
                ) {
                    $words = array_merge($words, $this->words);
                    $word_count = count($words);
                    $shuffle = false;
                }
            }
        }
        $words = array_slice($words, 0, $count);

        return $this->output($words, $tags, $array);
    }

    /**
     * Shuffle
     *
     * Shuffles the words, forcing "Lorem ipsum..." at the beginning if it is
     * the first time we are generating the text.
     *
     * @access private
     */
    protected function shuffle()
    {
        if ($this->first) {
            $this->firstWords = array_slice($this->words, 0, 8);
            $this->words = array_slice($this->words, 8);

            shuffle($this->words);

            $this->words = $this->firstWords + $this->words;

            $this->first = false;
        } else {
            shuffle($this->words);
        }
    }

    /**
     * Output
     *
     * Does the rest of the processing of the strings. This includes wrapping
     * the strings in HTML tags, handling transformations with the ability of
     * back referencing and determining if the passed array should be converted
     * into a string or not.
     *
     * @access private
     *
     * @param array $strings an array of generated strings
     * @param mixed $tags string or array of HTML tags to wrap output with
     * @param bool $array whether an array or a string should be returned
     * @param string $delimiter the string to use when calling implode()
     *
     * @return string|array   string or array of generated lorem ipsum text
     */
    protected function output(
        array $strings,
        array|string $tags,
        bool $array,
        string $delimiter = ' '
    ): string|array {
        if (empty($tags) === false) {
            if (!is_array($tags)) {
                $tags = [$tags];
            } else {
                // Flips the array so we can work from the inside out
                $tags = array_reverse($tags);
            }

            foreach ($strings as $key => $string) {
                foreach ($tags as $tag) {
                    // Detects / applies back reference
                    if ($tag[0] == '<') {
                        $string = str_replace('$1', $string, $tag);
                    } else {
                        $string = sprintf('<%1$s>%2$s</%1$s>', $tag, $string);
                    }

                    $strings[$key] = $string;
                }
            }
        }

        if (!$array) {
            $strings = implode($delimiter, $strings);
        }

        return $strings;
    }

    /**
     * Sentence
     *
     * Generates a full sentence of lorem ipsum.
     *
     * @access public
     *
     * @param mixed $tags string or array of HTML tags to wrap output with
     *
     * @return string generated lorem ipsum sentence
     */
    public function sentence(array|string $tags = ''): array|string
    {
        return $this->sentences(1, $tags);
    }

    /**
     * Sentences
     *
     * Generates sentences of lorem ipsum.
     *
     * @access public
     *
     * @param int $count how many sentences to generate
     * @param mixed $tags string or array of HTML tags to wrap output with
     * @param bool $array whether an array or a string should be returned
     *
     * @return mixed   string or array of generated lorem ipsum sentences
     */
    public function sentences(int|float $count = 1, array|string $tags = '', bool $array = false)
    {
        $sentences = [];

        for ($i = 0; $i < $count; $i++) {
            $sentences[] = $this->wordsArray($this->gauss(24.46, 5.08));
        }

        $this->punctuate($sentences);

        return $this->output($sentences, $tags, $array);
    }

    /**
     * Words Array
     *
     * Generates an array of lorem ipsum words.
     *
     * @access public
     *
     * @param int $count how many words to generate
     * @param mixed $tags string or array of HTML tags to wrap output with
     *
     * @return array   generated lorem ipsum words
     */
    public function wordsArray(int|float $count = 1, array|string $tags = ''): array
    {
        return $this->words($count, $tags, true);
    }

    /**
     * Gaussian Distribution
     *
     * This is some smart kid stuff. I went ahead and combined the N(0,1) logic
     * with the N(m,s) logic into this single function. Used to calculate the
     * number of words in a sentence, the number of sentences in a paragraph
     * and the distribution of commas in a sentence.
     *
     * @access private
     *
     * @param double $mean average value
     * @param double $std_dev stadnard deviation
     *
     * @return double  calculated distribution
     */
    protected function gauss(float $mean, float $std_dev)
    {
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
        $z = sqrt(-2 * log($x)) * cos(2 * M_PI * $y);

        return $z * $std_dev + $mean;
    }

    /**
     * Punctuate
     *
     * Applies punctuation to a sentence. This includes a period at the end,
     * the injection of commas as well as capitalizing the first letter of the
     * first word of the sentence.
     *
     * @access private
     *
     * @param array $sentences the sentences we would like to punctuate
     */
    protected function punctuate(array &$sentences): void
    {
        foreach ($sentences as $key => $sentence) {
            $words = count($sentence);

            // Only worry about commas on sentences longer than 4 words
            if ($words > 4) {
                $mean = log($words, 6);
                $std_dev = $mean / 6;
                $commas = round($this->gauss($mean, $std_dev));

                for ($i = 1; $i <= $commas; $i++) {
                    $word = round($i * $words / ($commas + 1));

                    if ($word < ($words - 1) && $word > 0) {
                        $sentence[$word] .= ',';
                    }
                }
            }

            $sentences[$key] = mb_ucfirst(implode(' ', $sentence) . '.');
        }
    }

    /**
     * Sentences Array
     *
     * Generates an array of lorem ipsum sentences.
     *
     * @access public
     *
     * @param int $count how many sentences to generate
     * @param mixed $tags string or array of HTML tags to wrap output with
     *
     * @return array   generated lorem ipsum sentences
     */
    public function sentencesArray(int|float $count = 1, array|string $tags = ''): array|string
    {
        return $this->sentences($count, $tags, true);
    }

    /**
     * Paragraph
     *
     * Generates a full paragraph of lorem ipsum.
     *
     * @access public
     *
     * @param mixed $tags string or array of HTML tags to wrap output with
     *
     * @return string generated lorem ipsum paragraph
     */
    public function paragraph(array|string $tags = ''): array|string
    {
        return $this->paragraphs(1, $tags);
    }

    /**
     * Paragraphss
     *
     * Generates paragraphs of lorem ipsum.
     *
     * @access public
     *
     * @param int $count how many paragraphs to generate
     * @param mixed $tags string or array of HTML tags to wrap output with
     * @param bool $array whether an array or a string should be returned
     *
     * @return mixed   string or array of generated lorem ipsum paragraphs
     */
    public function paragraphs(int|float $count = 1, array|string $tags = '', bool $array = false): mixed
    {
        $paragraphs = [];

        for ($i = 0; $i < $count; $i++) {
            $paragraphs[] = $this->sentences($this->gauss(5.8, 1.93));
        }

        return $this->output($paragraphs, $tags, $array, "\n\n");
    }

    /**
     * Paragraph Array
     *
     * Generates an array of lorem ipsum paragraphs.
     *
     * @access public
     *
     * @param int $count how many paragraphs to generate
     * @param mixed $tags string or array of HTML tags to wrap output with
     *
     * @return array   generated lorem ipsum paragraphs
     */
    public function paragraphsArray(int|float $count = 1, array|string $tags = ''): array
    {
        return $this->paragraphs($count, $tags, true);
    }
}
