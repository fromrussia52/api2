<?php

namespace App\Controller;

use App\Document\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Document\Generic;

class RegistrationController extends AbstractController
{
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $parent = $this->documentManager->find(null, '/users');
            if(!$parent){
                $parent = new Generic();
                $parent->setNodename('users');
                $core = $this->documentManager->find(null, '/');
                $parent->setParent($core);
                $this->documentManager->persist($parent);
            }
            $user->setParent($parent);
            $user->setNodename($form->get('username')->getData());
            $this->documentManager->persist($user);
            $this->documentManager->flush();

            // do anything else you need here, like send an email

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
