<?php

namespace Zephyr\CoursBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ferus\FairPayApi\FairPay;
use Ferus\FairPayApi\Exception\ApiErrorException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zephyr\CoursBundle\Entity\Course;
use Zephyr\CoursBundle\Entity\Student;
use Zephyr\CoursBundle\Form\CourseType;
use Zephyr\CoursBundle\Entity\Subject;
use Zephyr\CoursBundle\Entity\Unit;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $course = new Course();
        $form = $this->createForm(new CourseType($em), $course);

        if($request->isMethod('POST'))
        {
            $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($request->request->get('id'));

            if($student == null)
            {
                try{
                    $fairpay = new FairPay();
                    //$fairpay->setCurlParam(CURLOPT_HTTPPROXYTUNNEL, true);
                    //$fairpay->setCurlParam(CURLOPT_PROXY, "proxy.esiee.fr:3128");
                    $data = $fairpay->getStudent($request->request->get('id'));
                }
                catch(ApiErrorException $e){
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Code cantine incorrect.'
                    ));
                }

                $student = new Student();
                $student->setId($data->id);
                $student->setClass($data->class);
                $student->setFirstName($data->first_name);
                $student->setLastName($data->last_name);
                $student->setEmail($data->email);
                $password = str_shuffle('fOy4c9f5dV');
                $student->setPassword(password_hash($password, PASSWORD_DEFAULT));

                $message = \Swift_Message::newInstance()
                ->setSubject('[Cours-a-1-euro] Informations')
                ->setFrom(array('bde@edu.esiee.fr' => 'BDE ESIEE Paris'))
                ->setTo(array($student->getEmail() => $student->getFirstName() . ' ' . $student->getLastName()))
                ->setBody(
                    $this->renderView(
                        'ZephyrCoursBundle:Email:email.html.twig',
                        array(
                            'name' => $student->getFirstName(),
                            'code' => $password,
                            'id' => $student->getId(),
                        )
                    )
                );
                $this->get('mailer')->send($message);

                $em->persist($student);
                $em->flush();
            }
            else
            {
                if(!password_verify($request->request->get('password'), $student->getPassword()))
                {
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Le mot de passe est incorrect.'
                    ));
                }
            }

            $form->handleRequest($request);

            if(!$form->isValid())
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => 'Le formulaire est mal rempli. Avez-vous respecté la typo ? (Format matière : XXX-1234)'
                ));
            }

            $course_exist = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Course')->findBy(array(
                'unit' => $form->get('unit')->getData()->getName(),
                'date' => $form->get('date')->getData()
            ));

            if($course_exist != null)
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => 'Ce cours existe déjà.'
                ));
            }

            if(strcmp($form->get('date')->getData()->format('Y/m/d h:i'), date('Y/m/d h:i')) < 0)
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => "La date indiquée est inférieure à la date d'aujourd'hui."
                ));
            }

            if($this->getRequest()->request->get('submit') == 'prof')
            {
                $course->setProf($student);
            }
            elseif($this->getRequest()->request->get('submit') == 'eleve')
            {
                $course->addStudent($student);
            }

            $course->setSubject($form->get('unit')->getData()->getSubject()->getName());
            $course->setValid(0);
            $em->persist($course);
            $em->flush();

            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'success' => 'Votre demande de cours a été enregistrée.'
            ));
        }

        return $this->render('ZephyrCoursBundle:Default:index.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function mycourseAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if($request->isMethod('POST'))
        {
            $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($request->request->get('id'));

            if($student == null)
            {
                try{
                    $fairpay = new FairPay();
                    //$fairpay->setCurlParam(CURLOPT_HTTPPROXYTUNNEL, true);
                    //$fairpay->setCurlParam(CURLOPT_PROXY, "proxy.esiee.fr:3128");
                    $data = $fairpay->getStudent($request->request->get('id'));
                }
                catch(ApiErrorException $e){
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Code cantine incorrect.'
                    ));
                }

                $student = new Student();
                $student->setId($data->id);
                $student->setClass($data->class);
                $student->setFirstName($data->first_name);
                $student->setLastName($data->last_name);
                $student->setEmail($data->email);
                $password = str_shuffle('fOy4c9f5dV');
                $student->setPassword(password_hash($password, PASSWORD_DEFAULT));

                $message = \Swift_Message::newInstance()
                ->setSubject('[Cours-a-1-euro] Informations')
                ->setFrom(array('bde@edu.esiee.fr' => 'BDE ESIEE Paris'))
                ->setTo(array($student->getEmail() => $student->getFirstName() . ' ' . $student->getLastName()))
                ->setBody(
                    $this->renderView(
                        'ZephyrCoursBundle:Email:email.html.twig',
                        array(
                            'name' => $student->getFirstName(),
                            'code' => $password,
                            'id' => $student->getId(),
                        )
                    )
                );
                $this->get('mailer')->send($message);

                $em->persist($student);
                $em->flush();
            }
            else
            {
                if(!password_verify($request->request->get('password'), $student->getPassword()))
                {
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Le mot de passe est incorrect.'
                    ));
                }
            }

            $prof = $em->getRepository('ZephyrCoursBundle:Course')->findByProf($student->__toString());
            $eleve = $student->getCourses();
            $password = substr($student->getPassword(), 5, 10);

            return $this->render('ZephyrCoursBundle:Default:mycourses.html.twig', array(
                'id' => $student->getId(),
                'password' => $password,
                'prof' => $prof,
                'eleve' => $eleve
            ));
        }

        return $this->render('ZephyrCoursBundle:Default:mycourse.html.twig');
    }

    public function listcourseAction()
    {
        $em = $this->getDoctrine()->getManager();
        $courses = $em->getRepository('ZephyrCoursBundle:Course')->findAllOrdered();

        return $this->render('ZephyrCoursBundle:Default:listcourse.html.twig', array(
            'courses' => $courses, 
        ));
    }

    public function adminAction()
    {
        $em = $this->getDoctrine()->getManager();
        $courses = $em->getRepository('ZephyrCoursBundle:Course')->findAllOrdered();

        return $this->render('ZephyrCoursBundle:Default:admin.html.twig', array(
            'courses' => $courses)
        );
    }

    public function showAction(Course $course, Request $request)
    {
        $students = $course->getStudents();
        $em = $this->getDoctrine()->getManager();

        if($request->isMethod('POST'))
        {
            $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($request->request->get('id'));

            if ($this->getRequest()->request->get('submit') == 'addprof')
            {
                $course->setProf($student);
            }

            if ($this->getRequest()->request->get('submit') == 'addeleve')
            {
                if($student == null)
                {
                    try{
                        $fairpay = new FairPay();
                        //$fairpay->setCurlParam(CURLOPT_HTTPPROXYTUNNEL, true);
                        //$fairpay->setCurlParam(CURLOPT_PROXY, "proxy.esiee.fr:3128");
                        $data = $fairpay->getStudent($request->request->get('id'));
                    }
                    catch(ApiErrorException $e){
                        return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                            'error' => 'Code cantine incorrect.'
                        ));
                    }

                    $student = new Student();
                    $student->setId($data->id);
                    $student->setClass($data->class);
                    $student->setFirstName($data->first_name);
                    $student->setLastName($data->last_name);
                    $student->setEmail($data->email);
                    $password = str_shuffle('fOy4c9f5dV');
                    $student->setPassword(password_hash($password, PASSWORD_DEFAULT));

                    $message = \Swift_Message::newInstance()
                    ->setSubject('[Cours-a-1-euro] Informations')
                    ->setFrom(array('bde@edu.esiee.fr' => 'BDE ESIEE Paris'))
                    ->setTo(array($student->getEmail() => $student->getFirstName() . ' ' . $student->getLastName()))
                    ->setBody(
                        $this->renderView(
                            'ZephyrCoursBundle:Email:email.html.twig',
                            array(
                                'name' => $student->getFirstName(),
                                'code' => $password,
                                'id' => $student->getId(),
                            )
                        )
                    );
                    $this->get('mailer')->send($message);

                    $em->persist($student);
                    $em->flush();
                }

                if($student->__toString() == $course->getProf())
                {
                    return $this->render('ZephyrCoursBundle:Default:successAdmin.html.twig', array(
                        'error' => 'Cet élève est le professeur, il ne peut pas être un élève de son propre cours.'
                    ));
                }

                try{
                    $course->addStudent($student);
                    $em->persist($course);
                    $em->flush();

                    return $this->render('ZephyrCoursBundle:Default:successAdmin.html.twig', array(
                        'success' => 'Opération effectuée avec succès.'
                    ));
                }
                catch(\Exception $e){
                    return $this->render('ZephyrCoursBundle:Default:successAdmin.html.twig', array(
                        'error' => 'Cet étudiant est déjà un élève du cours.'
                    ));
                }
            }

            if ($this->getRequest()->request->get('submit') == 'delprof')
            {
                $course->setProf(NULL);
            }

            if ($this->getRequest()->request->get('submit') == 'deleleve')
            {
                if($student == null){
                    return $this->render('ZephyrCoursBundle:Default:successAdmin.html.twig', array(
                        'error' => "Cet élève n'existe pas."
                    ));
                }

                try{
                    $course->removeStudent($student);
                    $em->persist($student);
                } 
                catch(\Exception $e){
                    return $this->render('ZephyrCoursBundle:Default:successAdmin.html.twig', array(
                        'error' => "Vous avez oublié de renseigner le nom de l'élève."
                    ));
                }
            }

            if ($this->getRequest()->request->get('submit') == 'validate')
            {
                $course->setValid(1);
                $em->persist($course);
            }

            if ($this->getRequest()->request->get('submit') == 'suppr')
            {
                $em->remove($course);
            }

            if ($this->getRequest()->request->get('submit') == 'note')
            {
                $course->setNote($_POST['note']);
                $em->persist($course);
            }

            $em->flush();

            return $this->render('ZephyrCoursBundle:Default:successAdmin.html.twig', array(
                'success' => 'Opération effectuée avec succès.'
            ));
        }

        return $this->render('ZephyrCoursBundle:Default:show.html.twig', array(
            'students' => $students,
            'course' => $course
        ));
    }

    public function addstudentAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $course = $em->getRepository('ZephyrCoursBundle:Course')->find($id);

        if($request->isMethod('POST'))
        {
           $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($request->request->get('id'));
            if($student == null)
            {
                try{
                    $fairpay = new FairPay();
                    //$fairpay->setCurlParam(CURLOPT_HTTPPROXYTUNNEL, true);
                    //$fairpay->setCurlParam(CURLOPT_PROXY, "proxy.esiee.fr:3128");
                    $data = $fairpay->getStudent($request->request->get('id'));
                }
                catch(ApiErrorException $e){
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Code cantine incorrect.'
                    ));
                }

                $student = new Student();
                $student->setId($data->id);
                $student->setClass($data->class);
                $student->setFirstName($data->first_name);
                $student->setLastName($data->last_name);
                $student->setEmail($data->email);
                $password = str_shuffle('fOy4c9f5dV');
                $student->setPassword(password_hash($password, PASSWORD_DEFAULT));

                $message = \Swift_Message::newInstance()
                ->setSubject('[Cours-a-1-euro] Informations')
                ->setFrom(array('bde@edu.esiee.fr' => 'BDE ESIEE Paris'))
                ->setTo(array($student->getEmail() => $student->getFirstName() . ' ' . $student->getLastName()))
                ->setBody(
                    $this->renderView(
                        'ZephyrCoursBundle:Email:email.html.twig',
                        array(
                            'name' => $student->getFirstName(),
                            'code' => $password,
                            'id' => $student->getId(),
                        )
                    )
                );
                $this->get('mailer')->send($message);

                $em->persist($student);
                $em->flush();
            }
            else
            {
                if(!password_verify($request->request->get('password'), $student->getPassword()))
                {
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Le mot de passe est incorrect.'
                    ));
                }
            }

            if($student->__toString() == $course->getProf())
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => 'Vous ne pouvez pas être élève de votre propre cours.'
                ));
            }

            if($course->getValid() == 1)
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => 'Ce cours a été archivé et ne peut plus être rejoint.'
                ));
            }

            try{
                $course->addStudent($student);
                $em->persist($student);
                $em->persist($course);
                $em->flush();
            } 
            catch(\Exception $e){
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => "Vous faites déjà parti de ce cours."
                ));
            }

            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'success' => "Vous avez été ajouté au cours en tant qu'élève."
            ));
        }

        return $this->render('ZephyrCoursBundle:Default:addstudent.html.twig', array(
            'course' => $course
        ));
    }

    public function addprofAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $course = $em->getRepository('ZephyrCoursBundle:Course')->find($id);

        if($request->isMethod('POST'))
        {
            $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($request->request->get('id'));
            if($student == null)
            {
                try{
                    $fairpay = new FairPay();
                    //$fairpay->setCurlParam(CURLOPT_HTTPPROXYTUNNEL, true);
                    //$fairpay->setCurlParam(CURLOPT_PROXY, "proxy.esiee.fr:3128");
                    $data = $fairpay->getStudent($request->request->get('id'));
                }
                catch(ApiErrorException $e){
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Code cantine incorrect.'
                    ));
                }

                $student = new Student();
                $student->setId($data->id);
                $student->setClass($data->class);
                $student->setFirstName($data->first_name);
                $student->setLastName($data->last_name);
                $student->setEmail($data->email);
                $password = str_shuffle('fOy4c9f5dV');
                $student->setPassword(password_hash($password, PASSWORD_DEFAULT));

                $message = \Swift_Message::newInstance()
                ->setSubject('[Cours-a-1-euro] Informations')
                ->setFrom(array('bde@edu.esiee.fr' => 'BDE ESIEE Paris'))
                ->setTo(array($student->getEmail() => $student->getFirstName() . ' ' . $student->getLastName()))
                ->setBody(
                    $this->renderView(
                        'ZephyrCoursBundle:Email:email.html.twig',
                        array(
                            'name' => $student->getFirstName(),
                            'code' => $password,
                            'id' => $student->getId(),
                        )
                    )
                );
                $this->get('mailer')->send($message);

                $em->persist($student);
                $em->flush();
            }
            else
            {
                if(!password_verify($request->request->get('password'), $student->getPassword()))
                {
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Le mot de passe est incorrect.'
                    ));
                }
            }

            if($student->__toString() == $course->getProf())
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => 'Vous êtes déjà le professeur de ce cours.'
                ));
            }

            if($course->getProf() != NULL)
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => 'Il existe déjà un professeur.'
                ));
            }

            $eleves = $course->getStudents();

            for($i = 0; $i < count($eleves); $i++)
            {
                if($student->getId() == $eleves[$i]->getId())
                {
                    return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                        'error' => 'Vous êtes déjà un élève de ce cours.'
                    ));
                }
            }

            $course->setProf($student);
            $em->persist($course);
            $em->persist($student);
            $em->flush();

            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'success' => "Vous avez été ajouté au cours en tant que prof."
            ));
        }

        return $this->render('ZephyrCoursBundle:Default:addprof.html.twig', array('course' => $course));
    }

    public function changepasswordAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if($request->isMethod('POST'))
        {
            $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($request->request->get('id'));
            if($student == null)
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => "Vous n'êtes pas encore inscrit à un cours, nous ne pouvons pas retrouver un mot de passe inexistant."
                ));
            }

            if ($this->getRequest()->request->get('submit') == 'lost')
            {
                $token = uniqid(rand(), true);
                $student->setToken($token);
                $em->persist($student);
                $em->flush();
                $message = \Swift_Message::newInstance()
                    ->setSubject('[Cours-a-1-euro] Mot de passe oublié')
                    ->setFrom(array('bde@edu.esiee.fr' => 'BDE ESIEE Paris'))
                    ->setTo(array($student->getEmail() => $student->getFirstName() . ' ' . $student->getLastName()))
                    ->setBody(
                    $this->renderView(
                        'ZephyrCoursBundle:Email:password.html.twig',
                        array(
                            'name' => $student->getFirstName(),
                            'token' => $token,
                            'id' => $student->getId(),
                        )
                    )
                );
                $this->get('mailer')->send($message);

                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                            'success' => "Vous avez reçu un email."
                ));
            }

            if(!password_verify($request->request->get('password'), $student->getPassword()))
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => "L'ancien mot de passe est incorrect."
                ));
            }

            if($request->request->get('newpassword') != $request->request->get('new2password'))
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => "Votre nouveau mot de passe ne correspond pas."
                ));
            }

            $student->setPassword(password_hash($request->request->get('newpassword'), PASSWORD_DEFAULT));
            $em->persist($student);
            $em->flush();

            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'success' => "Votre nouveau mot de passe a bien été enregistré."
            ));
        }

        return $this->render('ZephyrCoursBundle:Default:password.html.twig');
    }

    public function tokenpasswordAction(Request $request, $id, $token)
    {
        $em = $this->getDoctrine()->getManager();
        $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($id);

        if($request->isMethod('POST'))
        {
            if($student == null)
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => "Vous n'êtes pas encore inscrit à un cours, nous ne pouvons pas retrouver un mot de passe inexistant."
                ));
            }
        
            if($token != $student->getToken())
            {
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => 'Le lien a expiré, veuillez reformuler une autre demande.'
                ));
            }

            if($request->request->get('newpassword') != $request->request->get('new2password'))
                return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                    'error' => "Votre nouveau mot de passe ne correspond pas."
                ));

            $student->setPassword(password_hash($request->request->get('newpassword'), PASSWORD_DEFAULT));
            $student->setToken(NULL);
            $em->persist($student);
            $em->flush();

            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'success' => "Votre nouveau mot de passe a bien été enregistré."
            ));
        }

        return $this->render('ZephyrCoursBundle:Default:tokenpassword.html.twig', array(
            'id' => $id,
            'token' => $token
        ));
    }

    public function coursdelAction(Request $request, $id, $cours_id, $password)
    {
        $em = $this->getDoctrine()->getManager();
        $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($id);
        $course = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Course')->findOneById($cours_id);
        $token = substr($student->getPassword(), 5, 10);

        if($student == null)
        {
            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'error' => "Cet étudiant n'existe pas."
            ));
        }

        if($course == null)
        {
            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'error' => "Ce cours n'existe pas."
            ));
        }

        if($password != $token)
        {
            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'error' => "Erreur token expiré."
            ));
        }

        $em->remove($course);
        $em->flush();

        return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
            'success' => "Le cours a été supprimé."
        ));
    }

    public function coursremoveAction(Request $request, $id, $cours_id, $password)
    {
        $em = $this->getDoctrine()->getManager();
        $student = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findOneById($id);
        $course = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Course')->findOneById($cours_id);
        $token = substr($student->getPassword(), 5, 10);

        if($student == null)
        {
            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'error' => "Cet étudiant n'existe pas."
            ));
        }

        if($course == null)
        {
            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'error' => "Ce cours n'existe pas."
            ));
        }

        if($password != $token)
        {
            return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
                'error' => "Erreur token expiré."
            ));
        }

        $course->removeStudent($student);
        $em->persist($course);
        $em->flush();

        return $this->render('ZephyrCoursBundle:Default:success.html.twig', array(
            'success' => "Vous avez été enlevé du cours."
        ));
    }

	public function searchAction($query)
    {
        try{
            $fairpay = new FairPay();
            //$fairpay->setCurlParam(CURLOPT_HTTPPROXYTUNNEL, true);
            //$fairpay->setCurlParam(CURLOPT_PROXY, "proxy.esiee.fr:3128");
            $student = $fairpay->getStudent($query);
            $inBdd = $this->getDoctrine()->getRepository('ZephyrCoursBundle:Student')->findAsArray($student->id);

            if($inBdd != null)
                $student = $inBdd;
        }
        catch(ApiErrorException $e){
            return new Response(json_encode($e->returned_value), $e->returned_value->code);
        }

        return new Response(json_encode($student), 200);
    }
}