<?php

namespace App\Controller;

use App\Entity\Field;
use App\Entity\Parcel;
use App\Entity\UserPlant;
use App\Entity\YearPlan;
use App\Entity\Plant;
use App\Form\NewFieldFormType;
use App\Form\NewYearPlanFormType;
use App\Form\UserPlantsType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataController extends AbstractController {

    /**
     * @Route("yearPlan", name="chooseYearPlan")
     */
    public function chooseYearPlan() {
        $user = $this->getUser();
        $userYearPlans = $user->getYearPlans();
        return $this->render('data/chooseYearPlan.twig', ['yearPlanCollection' => $userYearPlans]);
    }

    /**
     * @Route("setYearPlan", name="setYearPlan")
     */
    public function setYearPlan(Request $request) {
        $user = $this->getUser();

// From POST
        $yearPlanId = $request->request->get('yearPlan');

        $yearPlan = DataController::findYearPlanById($yearPlanId);
        if ($yearPlan) {
            $user->setChoosedYearPlan($yearPlan);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
        }
        return $this->redirectToRoute('main');
    }

    /**
     * @Route("yearPlan/status", name="yearPlanStatus")
     */
    public function yearPlanChangeStatus(Request $request) {
        $user = $this->getUser();
        $userYearPlans = $user->getYearPlans();

// From POST
        $yearPlanId = $request->request->get('yearPlan');
        $status = $request->request->get('status');

        $yearPlan = DataController::findEntityById($userYearPlans, $yearPlanId);

        if ($yearPlan) {
// Change status
            $status == "open" ? $yearPlan->setIsClosed(true) : $yearPlan->setIsClosed(false);
// Save to database
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
        }
        return $this->redirectToRoute('yearPlanList');
    }

    /**
     * @Route("yearPlan/add", name="addYearPlan")
     */
    public function addYearPlan(ValidatorInterface $validator, Request $request) {
        $user = $this->getUser();
        $yearPlan = new YearPlan();
        $yearPlan->setUser($user);

        $form = $this->createForm(NewYearPlanFormType::class, $yearPlan, ['yearPlans' => $user->getYearPlans()]);
        $form->handleRequest($request);

        $errors = $validator->validate($yearPlan);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $yearPlanToImport = $form->get('import')->getData();
            $yearPlan->setEndYear($yearPlan->getStartYear() + 1);
            $entityManager->persist($yearPlan);

            if ($yearPlanToImport) { //Import settings from other YearPlan
                DataController::deepSave($yearPlanToImport, $yearPlan);
            }
            $entityManager->flush();
            return $this->redirectToRoute('chooseYearPlan');
        }

        $parameters = [
            'newYearPlanForm' => $form->createView(),
            'errors' => $errors
        ];
        return $this->render('data/newYearPlan.html.twig', $parameters);
    }

    /**
     * @Route("yearPlanList", name="yearPlanList")
     */
    public function yearPlan() {
        $user = $this->getUser();
        return $this->render('data/yearPlan.html.twig', ['yearPlan' => $user->getYearPlans()]);
    }

    /**
     * @Route("parcel", name="parcelList")
     */
    public function parcelList(Request $request) {
        $user = $this->getUser();
        $yearPlan = $user->getChoosedYearPlan();

        if ($yearPlan) { // If found yearPlan
            $fields = $yearPlan->getFields();
            $parameters = [
                'yearPlan' => $yearPlan,
                'start' => $yearPlan->getStartYear()
            ];
            return $this->render('data/parcel.html.twig', $parameters);
        }
        return $this->redirectToRoute('chooseYearPlan');
    }

    /**
     * @Route("fieldsTable", name="fieldsTable")
     */
    public function fieldsTable(Request $request) {
        $user = $this->getUser();
        $yearPlan = $user->getChoosedYearPlan();

        // if Not choosed
        if (!$yearPlan)
            return $this->redirectToRoute('chooseYearPlan');

        return $this->render('data/fieldsTable.twig', ['yearPlan' => $yearPlan]);
    }


    /**
     * @Route("fieldNewOrder", name="fieldNewOrder")
     */
    public function fieldNewOrder(Request $request) {
        $user = $this->getUser();
        $yearPlan = $user->getChoosedYearPlan();

        if ($yearPlan) { // If found yearPlan
            $entityManager = $this->getDoctrine()->getManager();
            foreach($yearPlan->getFields() as $field) {
                if($field->getNumber() >100) {
                    $number = (int) ($field->getNumber()/10);
                    $yearPlan->insertField($number,$field);
                    break;
                }
            
            }
            
            $entityManager->persist($yearPlan);
            $entityManager->flush();
        }

        return $this->redirectToRoute('field');
    }

        /**
     * @Route("fieldRenumerate", name="fieldRenumerate")
     */
    public function fieldRenumerate(Request $request) {
        $user = $this->getUser();
        $yearPlan = $user->getChoosedYearPlan();

        if ($yearPlan) { // If found yearPlan
            $entityManager = $this->getDoctrine()->getManager();
            $number = 1;
            foreach($yearPlan->getFields() as $field) {
                    $field->setNumber($number++);
                }
            }
            $entityManager->persist($yearPlan);
            $entityManager->flush();
            return $this->redirectToRoute('field');
        }


    /**
     * @Route("field", name="field")
     */
    public function field(Request $request) {
        $user = $this->getUser();
        $yearPlan = $user->getChoosedYearPlan();

        if ($yearPlan) { // If found yearPlan
            $fields = $yearPlan->getFields();
            $cultivatedArea = DataController::sumAreaEachField($fields);
            $parameters = [
                'start' => $yearPlan->getStartYear(),
                'field' => $fields,
                'cultivatedArea' => $cultivatedArea
            ];
            return $this->render('data/field.html.twig', $parameters);
        }

        return $this->redirectToRoute('chooseYearPlan');
    }

    /**
     * @Route("field/add", name="addField")
     */
    public function addField(Request $request) {
        $user = $this->getUser();
        $yearPlan = $user->getChoosedYearPlan();

        if ($yearPlan) {
            $operators = $yearPlan->getOperators();
            $entityManager = $this->getDoctrine()->getManager();

            $field = new Field();

            $field->setYearPlan($yearPlan);
            $field->setNewNumber();
            $form = $this->createForm(NewFieldFormType::class, $field, ['operators' => $operators]);
            $form->handleRequest($request);

            $errors = $form->getErrors(true);


            if ($form->isSubmitted() && $form->isValid()) {

                $this->addFlash('success', 'Pole ' . $field->getName() . ' zostało utworzone');
                $entityManager->persist($field);
                DataController::addYearToParcels($field, $entityManager);
                $entityManager->flush();

                unset($field);
                unset($form);
                $field = new Field();
                $form = $this->createForm(NewFieldFormType::class, $field, ['operators' => $operators]);
            }

            $parameters = [
                'yearPlan' => $yearPlan,
                'newFieldForm' => $form->createView(),
                'operators' => $operators,
                'errors' => $errors,
            ];
            return $this->render('data/newField.html.twig', $parameters);
        }
        return $this->redirectToRoute('chooseYearPlan');
    }

    /**
     * @Route("field/edit/{id}", name="editField")
     */
    public function editField($id, Request $request) {
        $user = $this->getUser();
        $yearPlan = $user->getChoosedYearPlan();

        if ($yearPlan) {
            $operators = $yearPlan->getOperators();
            $field = DataController::findFieldById($id, $user);
            if ($field) {
                $form = $this->createForm(NewFieldFormType::class, $field, array('operators' => $operators));
                $form->handleRequest($request);
                $errors = $form->getErrors(true);
                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();

                    if ($form->get('remove')->isClicked()) {
                        $entityManager->remove($field);
                        $entityManager->flush();
                        return $this->redirectToRoute('field');
                    }

                    DataController::addYearToParcels($field, $entityManager);

                    $entityManager->persist($field);
                    $entityManager->flush();
                    $this->addFlash('success', 'Pole ' . $field->getName() . ' zostało zmodyfikowane');
                }

                $parameters = [
                    'yearPlan' => $yearPlan,
                    'editFieldForm' => $form->createView(),
                    'operators' => $operators,
                    'errors' => $errors,
                ];

                return $this->render('data/editField.html.twig', $parameters);
            }
        }
        return $this->redirectToRoute('field');
    }

    /**
     * @Route("selectPlants", name="selectPlants")
     */
    public function selectPlants(Request $request) {


        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $plantList = $this->getDoctrine()->getRepository(Plant::class)->findAll();
        $userPlants = $user->getUserPlants();

        $form = $this->createForm(UserPlantsType::class, $user, ['plantList' => $plantList, 'userPlants' => $userPlants]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            DataController::addPlantsToUser($form, $user);
            $entityManager->flush();
            return $this->redirectToRoute('selectPlants');
        }
        $parameters = [
            'form' => $form->createView(),
        ];
        return $this->render('data/selectPlants.twig', $parameters);
    }

// Functions

    public function addPlantsToUser($form, $user) {
        $userPlants = $user->getUserPlants();
        foreach ($userPlants as $userPlant) {
            $user->removeUserPlant($userPlant);
        }
        $choosedPlant = $form->get('Plants')->getData();
        foreach ($choosedPlant as $plant) {
            $userPlant = new UserPlant();
            $userPlant->setUser($user);
            $userPlant->setPlant($plant);
            $user->addUserPlant($userPlant);
        }
    }

    public function sumAreaEachField(Collection $fields) {
        $user = $this->getUser();
        $cultivatedArea = Array();
        foreach ($fields as $item) {
            $parcels = $item->getParcels();
            $cultivatedAreaSum = 0;
            foreach ($parcels as $each) {
                $cultivatedAreaSum += $each->getCultivatedArea();
            }
            array_push($cultivatedArea, $cultivatedAreaSum / 100);
        }
        return $cultivatedArea;
    }

    public function findEntityById(Collection $collection, $id) {
        foreach ($collection as $item) {
            if ($item->getId() == $id)
                return $item;
        }
    }

    public function findFieldById($id, $user) {
        $repository = $this->getDoctrine()->getRepository(Field::class);
        $field = $repository->find($id);
        $userYearPlans = $user->getYearPlans();
        foreach ($userYearPlans as $userYearPlan) {
            if ($field->getYearPlan()->getId() == $userYearPlan->getId())
                return $field;
        }
    }

    public function addYearToParcels(Field $field, $entityManager) {
        foreach ($field->getParcels() as $item) {
            $item->setYearPlan($field->getYearPlan());
            $entityManager->persist($item);
        }
    }

    public function findYearPlanById($id) {
        $user = $this->getUser();
        $repository = $this->getDoctrine()->getRepository(YearPlan::class);
        $yearPlan = $repository->find($id);
        if ($yearPlan) {
            if ($yearPlan->getUser() == $user)
                return $yearPlan;
        }
    }

    public function deepSave($yearPlanToImport, $yearPlanOutput) {
        $entityManager = $this->getDoctrine()->getManager();

        $operatorsList = new ArrayCollection();
        foreach ($yearPlanToImport->getOperators() as $operator) {
            $operatorNew = clone $operator;
            $operatorNew->setYearPlan($yearPlanOutput);
            $entityManager->persist($operatorNew);
            $operatorsList->add($operatorNew);
        }

        foreach ($yearPlanToImport->getFields() as $field) {
            $fieldNew = clone $field;
            $fieldNew->setYearPlan($yearPlanOutput);
            foreach ($field->getParcels() as $parcel) {
                $parcelNew = clone $parcel;
                $parcelNew->setYearPlan($yearPlanOutput);
                $parcelNew->setField($fieldNew);
                $parcelNew->setArimrOperator(null);
                foreach ($operatorsList as $operator) {
                    if (!$parcel->getArimrOperator())
                        break;
                    if ($operator->getFirstName() == $parcel->getArimrOperator()->getFirstName() &&
                            $operator->getSurname() == $parcel->getArimrOperator()->getSurname()
                    ) {
                        $parcelNew->setArimrOperator($operator);
                    }
                }
                $entityManager->persist($parcelNew);
            }
            $entityManager->persist($fieldNew);
        }
    }

    public function deleteField($field, $entityManager) {
// with all parcels
        foreach ($field->getParcels() as $parcel) {
            $entityManager->remove($parcel);
        }
        $entityManager->remove($field);
        $entityManager->flush();
    }

}
