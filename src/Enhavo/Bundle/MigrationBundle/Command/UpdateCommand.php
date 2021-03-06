<?php
/**
 * UpdateCommand.php
 *
 * @since 2016-04-15
 * @author Fabian Liebl <fabian.liebl@xq-web.de>
 */

namespace Enhavo\Bundle\MigrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('enhavo:migration:update')
            ->setDescription('Update to higher enhavo version')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'start migration from version'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_OPTIONAL,
                'migrate to version'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting migration');
        $this->getContainer()->get('enhavo_migration.migrator')->run($input->getOption('from'), $input->getOption('to'));
        $output->writeln('Finished');
    }
}
