<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Console
 */

namespace Magebild\Console\Console\Command;

use Magebild\Console\Api\AssignmentManagementInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MassAssign extends Command
{
    const CATEGORY_ARGUMENT = "categories";
    const STORE_ARGUMENT = 'stores';

    const LIMIT_OPTION = 'limit';
    const DRYRUN_OPTION = 'dry-run';
    const LOG_OPTION = 'log';

    protected $assignmentManager;

    /**
     * MassAssign constructor.
     * @param AssignmentManagementInterface $assignmentManagement
     */
    public function __construct(
        AssignmentManagementInterface $assignmentManagement
    ) {
        $this->assignmentManager = $assignmentManagement;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $categories = $input->getArgument(self::CATEGORY_ARGUMENT);
        $stores = $input->getArgument(self::STORE_ARGUMENT);

        /** @var TYPE_NAME $categories */
        $categoriesArray = explode(',', $categories);
        $storeArray = explode(',', $stores);

        $limit = $input->getOption(self::LIMIT_OPTION);

        $dryrun = $input->getOption(self::DRYRUN_OPTION);
        $log = $input->getOption(self::LOG_OPTION);

        $this->assignmentManager->setOptions([
            'limit' => $limit,
            'dry-run' => $dryrun,
            'log' => $log
        ]);
        $this->assignmentManager->process($categoriesArray, $storeArray);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("magebild:massassign");
        $this->setDescription("Mass Assign products to website based given category ids");
        $this->setDefinition([
            new InputArgument(self::CATEGORY_ARGUMENT, InputArgument::REQUIRED, "Name"),
            new InputArgument(self::STORE_ARGUMENT, InputArgument::REQUIRED, 'Website Id'),
            new InputOption(self::LIMIT_OPTION, '-l', InputOption::VALUE_OPTIONAL, 'Record Limit'),
            new InputOption(self::DRYRUN_OPTION, '-d', InputOption::VALUE_NONE, 'Dry run'),
            new InputOption(self::LOG_OPTION, '-g', InputOption::VALUE_NONE, 'Log processes')
        ]);
        parent::configure();
    }
}
