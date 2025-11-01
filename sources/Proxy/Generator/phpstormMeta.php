<?php

namespace IPS\storm\Proxy\Generator;

use IPS\Application;
use IPS\storm\Writers\FileGenerator;

use function method_exists;
use function PHPSTORM_META\map;
use function PHPSTORM_META\override;

class phpstormMeta extends GeneratorAbstract
{
    public function create(): void
    {
        $body[] = <<<eof
<?php

namespace PHPSTORM_META {
eof;

        $body = array_merge($body, Store::i()->read('storm_metadata_final'));

        foreach (Application::appsWithExtension('storm', 'MetaData') as $app) {
            $extensions = $app->extensions('storm', 'MetaData', true);
            foreach ($extensions as $extension) {
                $extension->map($body);
            }
        }

        $body[] = "\n}";
        FileGenerator::i()
            ->setPath($this->save)
            ->setFileName('.phpstorm.meta')
            ->addBody(implode("\n", $body))
            ->save();
    }
}
