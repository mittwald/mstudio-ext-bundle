<?php

namespace Mittwald\MStudio\Bundle\Commands;

use Mittwald\MStudio\Bundle\Entity\ExtensionInstance;
use Mittwald\MStudio\Bundle\Repository\ExtensionInstanceRepository;
use Mittwald\MStudio\Bundle\Security\ExtensionInstanceSealer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "extension:seal",
    description: "Enforce encryption for all extension instances"
)]
class SealExtensionInstancesCommand extends Command
{
    private ExtensionInstanceRepository $repository;
    private ExtensionInstanceSealer $sealer;

    public function __construct(
        ExtensionInstanceRepository $repository,
        ExtensionInstanceSealer $sealer,
    )
    {
        $this->repository = $repository;
        $this->sealer = $sealer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $this->repository->findAll();

        foreach ($all as $instance) {
            $this->sealer->sealExtensionInstance($instance);
        }

        $this->repository->flush();

        return 0;
    }
}