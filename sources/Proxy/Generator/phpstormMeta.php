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

        foreach (Application::appsWithExtension('storm', 'ProxyHelpers') as $app) {
            $extensions = $app->extensions('storm', 'ProxyHelpers', true);
            foreach ($extensions as $extension) {
                if (method_exists($extension, 'phpstormMeta')) {
                    $extension->phpstormMeta($body);
                }
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
