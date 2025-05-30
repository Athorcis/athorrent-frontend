<?php

namespace Athorrent\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: "/tests", options: ['csrf' => false], env: "test")]
class TestsController extends AbstractController
{
    #[Route(path: "/reset-data", methods: "POST")]
    public function resetData(KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'tests:data:reset',
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $status = $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = $output->fetch();

        return new Response($content, $status === Command::SUCCESS ? 200 : 500);
    }
}
