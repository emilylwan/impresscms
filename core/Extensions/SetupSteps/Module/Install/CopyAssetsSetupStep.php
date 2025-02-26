<?php

namespace ImpressCMS\Core\Extensions\SetupSteps\Module\Install;

use Exception;
use Generator;
use icms_module_Object;
use ImpressCMS\Core\Extensions\SetupSteps\AddonAssetsTrait;
use ImpressCMS\Core\Extensions\SetupSteps\OutputDecorator;
use ImpressCMS\Core\Extensions\SetupSteps\SetupStepInterface;
use ImpressCMS\Core\Models\Module;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Flysystem\FileAttributes;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Copies module assets to public path
 *
 * @package ImpressCMS\Core\SetupSteps\Module\Install
 */
class CopyAssetsSetupStep implements SetupStepInterface, ContainerAwareInterface
{
	use ContainerAwareTrait, AddonAssetsTrait;

	/**
	 * @inheritDoc
	 */
	public function execute(Module $module, OutputDecorator $output, ...$params): bool
	{
		/**
		 * @var TranslatorInterface $trans
		 */
		$trans = $this->container->get('translator');

		$output->info(
			$trans->trans('ADDONS_COPY_ASSETS_INFO', [], 'addons')
		);
		$output->incrIndent();
		$output->msg(
			$trans->trans('ADDONS_COPY_ASSETS_DELETE_OLD', [], 'addons')
		);
		$this->recreateAssetsPublicFolderPath('modules/' . $module->dirname, 'modules');

		$assetsCopier = $this->copyAllAssets(
			(array)$module->getInfo('assets'),
			$module->dirname,
			'modules'
		);
		foreach ($assetsCopier as $assetPath) {
			$output->msg(
				$trans->trans('ADDONS_COPY_ASSETS_COPYING', ['%file%' => $assetPath], 'addons')
			);
		}

		$output->decrIndent();

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getPriority(): int
	{
		return 100;
	}
}
