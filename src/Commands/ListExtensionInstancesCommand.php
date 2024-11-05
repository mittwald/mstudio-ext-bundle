<?php

namespace Mittwald\MStudio\Bundle\Commands;

use Mittwald\MStudio\Bundle\Entity\ExtensionInstance;
use Mittwald\MStudio\Bundle\Repository\ExtensionInstanceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "extension:list",
    description: "List all extension instances"
)]
class ListExtensionInstancesCommand extends Command
{
    private ExtensionInstanceRepository $repository;

    public function __construct(ExtensionInstanceRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $this->repository->findAll();

        $table = new Table($output);
        $table->setHeaders(['ID', 'Context', 'Enabled']);
        $table->setRows(array_map(fn (ExtensionInstance $instance) => [
            $instance->getId(),
            $instance->getContext()->getKind() . ": " . $instance->getContext()->getId(),
            $instance->isEnabled() ? "yes" : "no",
        ], $all));

        $table->render();

        return 0;
    }
}