<?php

namespace Mittwald\MStudio\Bundle\Commands;

use Mittwald\MStudio\Bundle\Repository\ExtensionInstanceRepository;
use Mittwald\MStudio\Bundle\Security\ExtensionInstanceSealer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "extension:secret",
    description: "Get extension instance secret"
)]
class GetExtensionInstanceSecretCommand extends Command
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

    protected function configure()
    {
        $this->addArgument("instance-id", InputArgument::REQUIRED, "ID of the extension instance");
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument("instance-id");
        $instance = $this->repository->mustFind($id);

        $secret = $this->sealer->unsealExtensionInstanceSecret($instance->getSecret());
        $output->writeln($secret);

        return 0;
    }
}