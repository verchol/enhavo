<?php

namespace Enhavo\Bundle\NewsletterBundle\Controller;

use Enhavo\Bundle\AppBundle\Controller\ResourceController;
use Enhavo\Bundle\NewsletterBundle\Event\NewsletterEvents;
use Enhavo\Bundle\NewsletterBundle\Event\SubscriberEvent;
use Enhavo\Bundle\NewsletterBundle\Model\SubscriberInterface;
use Enhavo\Bundle\NewsletterBundle\Strategy\AcceptStrategy;
use Enhavo\Bundle\NewsletterBundle\Strategy\DoubleOptInStrategy;
use ProjectBundle\Event\PreValidationEvent;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriberController extends ResourceController
{
    public function activateAction(Request $request)
    {
        $strategy = $this->get('enhavo_newsletter.strategy_resolver')->resolve($request->get('type'));

        if(!$strategy instanceof DoubleOptInStrategy) {
            throw $this->createNotFoundException();
        }

        $token = $request->get('token');

        $subscriber = $this->repository->findOneBy([
            'token' => $token,
            'active' => false
        ]);

        if(!$subscriber instanceof SubscriberInterface) {
            throw $this->createNotFoundException();
        }

        $strategy->activateSubscriber($subscriber, $request->get('type'));

        return $this->render($strategy->getActivationTemplate(), [
            'subscriber' => $subscriber
        ]);
    }

    public function acceptAction(Request $request)
    {
        $strategy = $this->get('enhavo_newsletter.strategy_resolver')->resolve($request->get('type'));

        if(!$strategy instanceof AcceptStrategy) {
            throw $this->createNotFoundException();
        }

        $token = $request->get('token');

        $subscriber = $this->repository->findOneBy([
            'token' => $token,
            'active' => false
        ]);

        if(!$subscriber instanceof SubscriberInterface) {
            throw $this->createNotFoundException();
        }

        $strategy->activateSubscriber($subscriber, $request->get('type'));

        return $this->render($strategy->getActivationTemplate(), [
            'subscriber' => $subscriber
        ]);
    }

    public function addAction(Request $request)
    {
        $translator = $this->get('translator');
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);
        /** @var SubscriberInterface $subscriber */
        $subscriber = $this->newResourceFactory->create($configuration, $this->factory);

        $this->setSubscriberType($subscriber, $request);
        $this->getSubscriberManager()->createSubscriber($subscriber);
        $form = $this->createSubscriberForm($subscriber);
        $form->handleRequest($request);

        if($form->isValid()) {
            $message =  $this->getSubscriberManager()->addSubscriber($subscriber, $request->get('type'));
            return new JsonResponse([
                'message' => $translator->trans($message, [], 'EnhavoNewsletterBundle'),
                'subscriber' => $subscriber
            ]);
        } else {
            $errorResolver = $this->container->get('enhavo_contact.form_error_resolver');
            $errors = $errorResolver->getErrors($form);
            return new JsonResponse([
                'errors' => $errors,
                'subscriber' => $subscriber
            ], 400);
        }
    }

    private function setSubscriberType(SubscriberInterface $subscriber, Request $request)
    {
        $type = $request->get('type');
        $formResolver = $this->container->get('enhavo_newsletter.form_resolver');
        $resolveType = $formResolver->resolveType($type);
        if($resolveType === null) {
            throw $this->createNotFoundException('type could not be resolved');
        }
        $subscriber->setType($type);
    }

    private function createSubscriberForm(SubscriberInterface $subscriber)
    {
        $formResolver = $this->container->get('enhavo_newsletter.form_resolver');
        $formType = $formResolver->resolveType($subscriber->getType());
        $form = $this->get('form.factory')->create($formType, $subscriber);
        return $form;
    }

    protected function getSubscriberManager()
    {
        return $this->get('enhavo_newsletter.subscriber.manager');
    }
}
