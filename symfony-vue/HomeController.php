<?php

// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;

use App\Entity\Stub;
use App\Entity\StubAttribute;
use App\Entity\Attribute;
use App\Entity\StubSubAttribute;
use App\Entity\StubAttributeProperty;
use App\Entity\AttributeRelationCategory;
use App\Entity\AttributeRelation;

class HomeController extends AbstractController
{

    private function _group_by($array, $key) {
      $return = array();
      foreach($array as $val) {
          $return[$val[$key]][] = $val;
      }
      return $return;
    }

    // Credits: https://gist.github.com/mcaskill/baaee44487653e1afc0d
    private function array_group_by(array $array, $key)
    {
        if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key) ) {
            trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
            return null;
        }
        $func = (!is_string($key) && is_callable($key) ? $key : null);
        $_key = $key;
        // Load the new array, splitting by the target key
        $grouped = [];
        foreach ($array as $value) {
            $key = null;
            if (is_callable($func)) {
                $key = call_user_func($func, $value);
            } elseif (is_object($value) && isset($value->{$_key})) {
                $key = $value->{$_key};
            } elseif (isset($value[$_key])) {
                $key = $value[$_key];
            }
            if ($key === null) {
                continue;
            }
            $grouped[$key][] = $value;
        }
        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $params = array_merge([ $value ], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $params);
            }
        }
        return $grouped;
    }

    protected function getUserInfo()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser()->info();
        return $user;
    }

    /** 
    * @Route("/",name="home")
    * @Route("/messages")
    * @Route("/profile/{id}")
    * @Route("/create-new-attribute")
    */ 
     
   public function home($id='')
   {
     return $this->render('frontend/index.html.twig');
   }

   /** 
   * @Route("/ajax")
   */ 
   public function getUsers()
   {
    $jsonData = array();  

    $users = $this->getDoctrine() 
      ->getRepository('App\Entity\User')
    ->findAll();

    foreach ($users as $user) {
        $jsonData = [$user->getEmail()];
    }

    return new JsonResponse($jsonData);

   }

   /** 
   * @Route("/api/v1/stub/create")
   */ 
   public function stub_create(Request $request)
   {

      $data = $request->getContent();
      $data = json_decode($data);

      $userId = $this->getUserInfo()['id'];

      if ($this->isCsrfTokenValid('create', $data->token)) {


         $user_ins = $this->getDoctrine() 
           ->getRepository('App\Entity\User') 
           ->find($userId);

         $stub_cat_ins = $this->getDoctrine() 
           ->getRepository('App\Entity\StubCategory')
           ->find(2);

         $stub_mat_ins = $this->getDoctrine() 
           ->getRepository('App\Entity\StubMatching')
           ->find($data->stubMatchingId);


         $stub = new Stub();
         $stub->setUserId($user_ins);
         $stub->setDefaultStub(0); // Would be removed later
         $stub->setStubName($data->stubName);
         $stub->setPostalCode($data->postalCode);
         $stub->setDistance($data->distance);
         $stub->setStubCategoryId($stub_cat_ins);
         $stub->setStubMatchingId($stub_mat_ins);
         $stub->setCreatedAt(new \DateTime());
         $stub->setUpdatedAt(new \DateTime());


         $doct = $this->getDoctrine()->getManager();
         $doct->persist($stub);
         $doct->flush();

         $jsonData = ['success'=>true,'id'=>$stub->getStubId()];
         return new JsonResponse($jsonData);

      }

      $jsonData = ['success'=>false];
      return new JsonResponse($jsonData);

   }

   /** 
   * @Route("/api/v1/stub/view")
   */ 
   public function stub_view(Request $request)
   {

        $userId = $this->getUserInfo()['id'];

        $post = $request->getContent();
        $post = json_decode($post);

        if($post->id=='default'){
            $where = ['stubCategoryId'=>1,'userId' => $userId];
        }else{
            $where = ['stubId'=>$post->id,'userId' => $userId];
        }

        $doct = $this->getDoctrine()->getManager(); 
        $stub = $doct->getRepository('App\Entity\Stub')->findOneBy($where);

        $defaultStub = ($stub->getStubCategoryId()->getStubCategoryId()==1)?true:false;


        $data = [
          'stubId'=>$stub->getStubId(),
          'stubName'=>$stub->getStubName(),
          'distance'=>$stub->getDistance(),
          'postalCode'=>$stub->getPostalCode(),
          'stubMatchingId'=>$stub->getStubMatchingId()->getStubMatchingId(),
          'defaultStub'=>$defaultStub
        ];

      $jsonData = ['success'=>true,'data'=>$data];

      return new JsonResponse($jsonData);

   }


   /** 
   * @Route("/api/v1/stub/update")
   */ 
   public function stub_update(Request $request)
   {

        $userId = $this->getUserInfo()['id'];

        $post = $request->getContent();
        $post = json_decode($post);

        $doct = $this->getDoctrine()->getManager();


        if($post->id=='default'){
            $where = ['stubCategoryId'=>1,'userId' => $userId];
            $stub = $doct->getRepository('App\Entity\Stub')->findOneBy($where);
        }else{
            $stub = $doct->getRepository('App\Entity\Stub')->find($post->id);
        }

        
        if (!$stub) {
            throw $this->createNotFoundException(
                'No product found for id '.$post->id
            );
        }

        $stub_mat_ins = $this->getDoctrine() 
        ->getRepository('App\Entity\StubMatching')
        ->find($post->stubMatchingId);

        $stub->setStubName($post->stubName);
        $stub->setPostalCode($post->postalCode);
        $stub->setDistance($post->distance);
        $stub->setStubMatchingId($stub_mat_ins);
        $stub->setUpdatedAt(new \DateTime());

        $doct->flush();

        if(count($post->attributes)){
          foreach ($post->attributes as $attribute) {
                $stubAttribute = $doct->getRepository('App\Entity\StubAttribute')->findOneBy(['stubAttributeID'=>$attribute->stubAttributeID]);
                $stubAttribute->setAttributeValue($attribute->attributeValue);
                $stubAttribute->setUpdatedAt(new \DateTime());
                $doct->flush();
          }

        }

        $jsonData = ['success'=>true];
        return new JsonResponse($jsonData);

   }


   /** 
   * @Route("/api/v1/stub/sub/update")
   */ 
   public function stub_sub_update(Request $request)
   {

        $userId = $this->getUserInfo()['id'];

        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();

        $stubAttribute = $entityManager
         ->getRepository('App\Entity\StubAttribute') 
         ->find($post->stubAttributeID);

        $attributeRelationCategory = $entityManager 
         ->getRepository('App\Entity\AttributeRelationCategory')
         ->find($post->attributeRelationCategoryId);

        $attribute = $entityManager 
         ->getRepository('App\Entity\Attribute')
         ->find($post->attributeId);


        $stubSubAttribute = $entityManager->getRepository('App\Entity\StubSubAttribute')->findOneBy(
        [
            'stubAttributeID' => $stubAttribute,
            'attributeRelationCategoryId' => $attributeRelationCategory,
        ]);

        if($stubSubAttribute){

          // Here Delete
          $stubAttributeProperty = $entityManager->getRepository('App\Entity\StubAttributeProperty')->findBy(
          [
              'stubSubAttributeID' => $stubSubAttribute->getStubSubAttributeID(),
          ]);
          if($stubAttributeProperty){
            foreach ($stubAttributeProperty as $single) {
              $entityManager->remove($single);
            }
            $entityManager->flush();
          }

          $stubSubAttribute->setAttributeId($attribute);
          $stubSubAttribute->setUpdatedAt(new \DateTime());
          $entityManager->flush();
          $status = 'udpated';
          $data = [];
        }else{
          $stubSubAttribute = new StubSubAttribute();
          $stubSubAttribute->setStubAttributeID($stubAttribute);
          $stubSubAttribute->setAttributeId($attribute);
          $stubSubAttribute->setAttributeRelationCategoryId($attributeRelationCategory);
          $stubSubAttribute->setCreatedAt(new \DateTime());
          $stubSubAttribute->setUpdatedAt(new \DateTime());
          $entityManager->persist($stubSubAttribute);
          $entityManager->flush();
          $status = 'created';
          $data = [
            'stubSubAttributeID'=>$stubSubAttribute->getStubSubAttributeID(),
            'attributeId'=>$stubSubAttribute->getAttributeId()->getAttributeId(),
          ];
        }

        $jsonData = ['success'=>true,'status'=>$status,'data'=>$data];
        return new JsonResponse($jsonData);

   }

   /** 
   * @Route("/api/v1/stub/prop/update")
   */ 
   public function stub_prop_update(Request $request)
   {

        $userId = $this->getUserInfo()['id'];

        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();

        $stubSubAttribute = $entityManager
         ->getRepository('App\Entity\StubSubAttribute') 
         ->find($post->stubSubAttributeID);

        $attributeProperty = $entityManager 
         ->getRepository('App\Entity\AttributeProperty')
         ->find($post->attributePropertyId);

        if($post->type=='select'){
          $attributePropertyItems = $entityManager 
           ->getRepository('App\Entity\AttributePropertyItems')
           ->find($post->attributePropertyItemsId);
        }

        $entityManager = $this->getDoctrine()->getManager();


        $stubAttributeProperty = $entityManager->getRepository('App\Entity\StubAttributeProperty')->findOneBy(
        [
            'stubSubAttributeID' => $stubSubAttribute,
            'attributePropertyId' => $attributeProperty,
        ]);

        if($stubAttributeProperty){
          if($post->type=='select') $stubAttributeProperty->setAttributePropertyItemsId($attributePropertyItems);
          if($post->type=='text') $stubAttributeProperty->setValue($post->value);
          $stubAttributeProperty->setUpdatedAt(new \DateTime());
          $entityManager->flush();
          $status = 'udpated';
          $data = [];
        }else{
          $stubAttributeProperty = new StubAttributeProperty();
          $stubAttributeProperty->setStubSubAttributeID($stubSubAttribute);
          $stubAttributeProperty->setAttributePropertyId($attributeProperty);
          if($post->type=='select')  $stubAttributeProperty->setAttributePropertyItemsId($attributePropertyItems);
          if($post->type=='text') $stubAttributeProperty->setValue($post->value);
          $stubAttributeProperty->setType($post->type);
          $stubAttributeProperty->setCreatedAt(new \DateTime());
          $stubAttributeProperty->setUpdatedAt(new \DateTime());
          $entityManager->persist($stubAttributeProperty);
          $entityManager->flush();
          $status = 'created';

          $data = [
            'stubAttributePropertyID'=>$stubAttributeProperty->getStubAttributePropertyID(),
            //'stubAttributeID'=>$value->getStubAttributeID()->getStubAttributeID(),
            'attributePropertyId'=>$stubAttributeProperty->getAttributePropertyId()->getAttributePropertyId(),
            'stubSubAttributeID'=>$stubAttributeProperty->getStubSubAttributeID()->getStubSubAttributeID(),
            'value'=>$stubAttributeProperty->getValue(),
          ];

          if($post->type=='select') $data['attributePropertyItemsId'] = $stubAttributeProperty->getAttributePropertyItemsId()->getAttributePropertyItemsId();
          else $data['attributePropertyItemsId'] = null;

        }

        $jsonData = ['success'=>true,'status'=>$status,'data'=>$data];
        return new JsonResponse($jsonData);

   }

   /** 
   * @Route("/api/v1/stub/prop/delete")
   */ 
   public function stub_prop_delete(Request $request)
   {
        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();

        $stubAttributeProperty = $entityManager->getRepository('App\Entity\StubAttributeProperty')->find($post->stubAttributePropertyID);

        if($stubAttributeProperty){
          $entityManager->remove($stubAttributeProperty);
          $entityManager->flush();
          $jsonData = ['success'=>true];
        }else{
          $jsonData = ['success'=>false];
        }

        return new JsonResponse($jsonData);

   }

    /** 
    * @Route("/api/v1/stub/list")
    */ 
    public function stub_list(Request $request)
    {


        $post = $request->getContent();
        $post = json_decode($post);

        $userId = $this->getUserInfo()['id'];

        $doct = $this->getDoctrine()->getManager(); 
        $stubs = $doct->getRepository('App\Entity\Stub')->findBy(
        [
            'userId' => $userId,
        ]);

        $data = [];
        foreach ($stubs as $stub) {

            $stubName = ($stub->getStubCategoryId()->getStubCategoryId()==1)?'Default Stub':$stub->getStubName();

            $data[] = [
              'id'=>$stub->getStubId(),
              'stubName'=>$stubName,
              'stubCategoryId'=>$stub->getStubCategoryId()->getStubCategoryId(),
            ];
        }

        $jsonData = ['success'=>true,'data'=>$data];
        return new JsonResponse($jsonData);

    }

    /** 
    * @Route("/api/v1/attribute/list")
    */ 
    public function attribute_list(Request $request)
    {

        $post = $request->getContent();
        $post = json_decode($post);

        if($post->stubCategoryId=='default'){
            
        }else{

        }

        $doct = $this->getDoctrine()->getManager(); 
        $attributes = $doct->getRepository('App\Entity\AttributeCategory')->findBy(
        [
            'stubCategoryId' => $post->stubCategoryId,
        ]);

        $data = [];
        foreach ($attributes as $attribute) {
            $data[] = $attribute->getAttributeCategoryId();
        }



        $attributes = $doct->getRepository('App\Entity\Attribute')->createQueryBuilder('a')
            ->andWhere('a.attributeCategoryId IN ('.implode(',', $data).')')
            ->getQuery()->execute();

        $data = [];
        foreach ($attributes as $attribute) {
            $data[] = [
                'attributeId'=>$attribute->getAttributeId(),
                'name'=>$attribute->getName(),
                'description'=>$attribute->getDescription(),
            ];
        }

        $jsonData = ['success'=>true,'data'=>$data];
        return new JsonResponse($jsonData);

    }    

    /** 
    * @Route("/api/v1/stub/attribute/add")
    */ 
    public function stub_attribute_add(Request $request)
    {
        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();

        $stub = $entityManager
         ->getRepository('App\Entity\Stub') 
         ->find($post->stubId);

        $attribute = $entityManager 
         ->getRepository('App\Entity\Attribute')
         ->find($post->attributeId);

        $stubAttribute = new StubAttribute();
        $stubAttribute->setStubId($stub);
        $stubAttribute->setAttributeId($attribute);
        $stubAttribute->setCreatedAt(new \DateTime());
        $stubAttribute->setUpdatedAt(new \DateTime());

        $entityManager->persist($stubAttribute);
        $entityManager->flush();

        $data = [
            'attributeId'=>$post->attributeId,
            'description'=>$attribute->getDescription(),
            'name'=>$attribute->getName(),
            'stubAttributeID'=>$stubAttribute->getStubAttributeID(),
        ];

        $jsonData = ['success'=>true,'data'=>$data];
        return new JsonResponse($jsonData);

    }

    /** 
    * @Route("/api/v1/stub/attribute/delete")
    */ 
    public function stub_attribute_delete(Request $request)
    {
        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();
        $stubAttribute = $entityManager->getRepository('App\Entity\StubAttribute')->find($post->stubAttributeID);

        /* Delete All Related values here */
        $stubSubAttribute = $entityManager->getRepository('App\Entity\StubSubAttribute')->findBy([
          'stubAttributeID'  => $stubAttribute
        ]);

        if($stubSubAttribute){

          foreach ($stubSubAttribute as $stubSubAttributeSingle) {

              $stubAttributeProperty = $entityManager->getRepository('App\Entity\StubAttributeProperty')->findBy(
              [
                  'stubSubAttributeID' => $stubSubAttributeSingle->getStubSubAttributeID(),
              ]);

              if($stubAttributeProperty){
                foreach ($stubAttributeProperty as $single) {
                  $entityManager->remove($single);
                }
                $entityManager->flush();
              }

              $entityManager->remove($stubSubAttributeSingle);

          }

          $entityManager->flush();

        }

        $entityManager->remove($stubAttribute);
        $entityManager->flush();

        $jsonData = ['success'=>true, 'data'=>$post->stubAttributeID];
        return new JsonResponse($jsonData);

    }

    /** 
    * @Route("/api/v1/stub/attribute/list")
    */ 
    public function stub_attribute_list(Request $request)
    {
        $post = $request->getContent();
        $post = json_decode($post);

        $doct = $this->getDoctrine()->getManager(); 
        $attributes = $doct->getRepository('App\Entity\StubAttribute')->findBy(
        [
            'stubId' => $post->stubID,
        ]);

        $data = [];
        foreach ($attributes as $attribute) {
            $data[] = [
              'attributeId'=>$attribute->getAttributeId()->getAttributeId(),
              'stubAttributeID'=>$attribute->getStubAttributeID(),
              'name'=>$attribute->getAttributeId()->getName(),
              'description'=>$attribute->getAttributeId()->getDescription(),
              'attributeValue'=>$attribute->getAttributeValue(),
            ];
        }

        $jsonData = ['success'=>true,'data'=>$data];
        return new JsonResponse($jsonData);

    }

    /** 
    * @Route("/api/v1/stub/attribute/sub")
    */ 
    public function stub_attribute_sub(Request $request)
    {
        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();

        $stubAttribute = $entityManager->getRepository('App\Entity\StubAttribute')->find($post->stubAttributeID);

        $attributeId = $stubAttribute->getAttributeId(); //obj

        $attributeRelation = $entityManager->getRepository('App\Entity\AttributeRelation')
          ->findBy([
            'parentAttributeId'=>$attributeId
          ]);

        $categories = [];
        $relations = [];
        $propertyRelations = [];
        $properties = [];
        $all_attributes = [$attributeId->getAttributeId()];
        $all_properties = [];
        $all_subattributes = [];
        $selectedRelations = [];
        $selectedProperties = [];


        foreach ($attributeRelation as $key => $value) {
          $relations[] = [
            'name'=>$value->getAttributeId()->getName(),
            'attributeId'=>$value->getAttributeId()->getAttributeId(),
            'attributeRelationCategoryId'=>$value->getAttributeRelationCategoryId()->getAttributeRelationCategoryId(),
          ];
          $categories[$value->getAttributeRelationCategoryId()->getAttributeRelationCategoryId()] = ['name'=>$value->getAttributeRelationCategoryId()->getName()];
          $all_attributes[] = $value->getAttributeId()->getAttributeId();
        }

        
        $attributeValues = $entityManager->getRepository('App\Entity\AttributeValues')->createQueryBuilder('a')
            ->andWhere('a.attributeId IN ('.implode(',', $all_attributes).')')
            ->getQuery()->execute();



        // Way 1
        // foreach ($attributeValues as $key => $value) {
        //   $propertyRelations[] = [
        //     'attributeId'=>$value->getAttributeId()->getAttributeId(),
        //     'attributePropertyId'=>$value->getAttributePropertyId()->getAttributePropertyId(),
        //   ];
        // }
        //$propertyRelations = $this->_group_by($propertyRelations,'attributeId');

        // Way 2
        foreach ($attributeValues as $key => $value) {

            array_push($all_properties, $value->getAttributePropertyId()->getAttributePropertyId());

            if(array_key_exists($value->getAttributeId()->getAttributeId(), $propertyRelations))
            {
              array_push(
                $propertyRelations[$value->getAttributeId()->getAttributeId()],
                [
                 'attributePropertyId' =>$value->getAttributePropertyId()->getAttributePropertyId(),
                 'visible'=>false,
                ] 
                
              );
            }
            else 
            {
              $propertyRelations[$value->getAttributeId()->getAttributeId()][] = [
                'attributePropertyId'=>$value->getAttributePropertyId()->getAttributePropertyId(),
                'visible'=>false,
              ];
            }
        } 


        $all_properties = array_unique($all_properties);


        if(count($all_properties)){

            $attributeProperty = $entityManager->getRepository('App\Entity\AttributeProperty')->createQueryBuilder('a')
                ->andWhere('a.attributePropertyId IN ('.implode(',', $all_properties).')')
                ->getQuery()->execute();

            foreach ($attributeProperty as $key => $value) {

                $items = [];

                $attributePropertyItems = $entityManager->getRepository('App\Entity\AttributePropertyItems')
                  ->findBy(
                    ['attributePropertyId'=>$value]
                  );

                if(count($attributePropertyItems)){

                  foreach ($attributePropertyItems as $k => $v) {
                    $items[] = [
                      'name'=>$v->getName(),
                      'itemOrder'=>$v->getItemOrder(),
                      'attributePropertyItemsId'=>$v->getAttributePropertyItemsId(),
                    ];
                  }

                  $tmp = array();
                  foreach ($items as $x => $y)
                  {
                      $tmp[$x] = $y['itemOrder'];
                  }
                  array_multisort($tmp, SORT_ASC, $items);

                }



                $properties[$value->getAttributePropertyId()] = [
                  'name'=>$value->getname(),
                  'isNumericValue'=>$value->getIsNumericValue(),
                  'rangeValueUp'=>$value->getRangeValueUp(),
                  'rangeValueDown'=>$value->getRangeValueDown(),
                  'isSelect'=>$value->getIsSelect(),
                  'isCheckbox'=>$value->getIsCheckbox(),
                  'items'=>$items,
                ];

            }

        }


        $relations = $this->_group_by($relations,'attributeRelationCategoryId');

        $stubSubAttribute = $entityManager->getRepository('App\Entity\StubSubAttribute')->findBy(
        [
            'stubAttributeID' => $post->stubAttributeID
        ]);



        foreach ($stubSubAttribute as $key => $value) {
            // Only unique relation for one category & one one attribute at a time
            $selectedRelations[$value->getAttributeRelationCategoryId()->getAttributeRelationCategoryId()] = [
              'stubSubAttributeID'=>$value->getStubSubAttributeID(),
              'attributeId'=>$value->getAttributeId()->getAttributeId(),
              'attributeRelationCategoryId'=>$value->getAttributeRelationCategoryId()->getAttributeRelationCategoryId(),
              'value'=>$value->getValue(),
            ];

            array_push($all_subattributes, $value->getStubSubAttributeID()); 
        }


        if(count($all_subattributes)){

          $stubAttributeProperty = $entityManager->getRepository('App\Entity\StubAttributeProperty')->createQueryBuilder('a')
              ->andWhere('a.stubSubAttributeID IN ('.implode(',', $all_subattributes).')')
              ->getQuery()->execute();

          

          foreach ($stubAttributeProperty as $key => $value) {
              $type = $value->getType();
              $attributePropertyItemsId = ($type=='select')?$value->getAttributePropertyItemsId()->getAttributePropertyItemsId():null;
              $selectedProperties[] = [
                'stubAttributePropertyID'=>$value->getStubAttributePropertyID(),
                'type'=>$value->getType(),
                //'stubAttributeID'=>$value->getStubAttributeID()->getStubAttributeID(),
                'attributePropertyId'=>$value->getAttributePropertyId()->getAttributePropertyId(),
                'stubSubAttributeID'=>$value->getStubSubAttributeID()->getStubSubAttributeID(),
                'attributePropertyItemsId'=>$attributePropertyItemsId,
                'value'=>$value->getValue(),
              ];
          }

          $selectedProperties = $this->_group_by($selectedProperties,'stubSubAttributeID');



          foreach ($selectedProperties as $key => $value) {
              $tmp = [];
              foreach ($value as $k => $v) $tmp[$v['attributePropertyId']] = $v;
              $selectedProperties[$key] = $tmp;
          }

        }else{

          $selectedProperties = [];
          $cats = array_keys($categories);
          foreach ($cats as $key => $value) {
            $selectedProperties[$value] = ['properties'];
          }

        }


        foreach ($selectedRelations as $key => $value) {

          if(isset($selectedProperties[$value['stubSubAttributeID']])) {
            $addProperties = $selectedProperties[$value['stubSubAttributeID']];

            $tmp = [];
            foreach ($propertyRelations[$value['attributeId']] as $x => $y) {
              array_push($tmp, $y['attributePropertyId']);
            }
            $tmp2 = array_keys($addProperties);
            $tmp3 = array_diff($tmp,$tmp2);
            foreach ($tmp3 as $x => $y) {
              $addProperties[$y] = ['attributePropertyId'=>'','attributePropertyItemsId'=>''];
            }
            $selectedRelations[$key]['properties'] = $addProperties;


          }else{

            $addProperties = [];

            if(isset($propertyRelations[$value['attributeId']])){

              $tmp = [];
              foreach ($propertyRelations[$value['attributeId']] as $x => $y) {
                array_push($tmp, $y['attributePropertyId']);
              }
              foreach ($tmp as $x => $y) {
                $addProperties[$y] = ['attributePropertyId'=>'','attributePropertyItemsId'=>''];
              }

            }
            $selectedRelations[$key]['properties'] = $addProperties;
          }

        }


        //$selectedRelations = [1=>[ 'properties'=>[] ],2=>['properties'=>[] ] ];





        $jsonData = [
          'success'=>true,
          'categories'=>$categories,
          'relations'=>$relations,
          'propertyRelations'=>$propertyRelations,
          'properties'=>$properties,
          'selectedRelations'=>$selectedRelations,
        ];

        return new JsonResponse($jsonData);

    }

    /** 
    * @Route("/api/v1/stub/delete")
    */ 
    public function stub_delete(Request $request)
    {
        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();
        $stubAttributes = $entityManager->getRepository('App\Entity\StubAttribute')->findBy(
          ['stubId'=>$post->stubId]
        );


        if(count($stubAttributes)){
          foreach ($stubAttributes as $stubAttribute) {
              $entityManager->remove($stubAttribute);
              $entityManager->flush();
          }
        }


        $stub = $entityManager->getRepository('App\Entity\Stub')->find($post->stubId);
        $entityManager->remove($stub);
        $entityManager->flush();

        $jsonData = ['success'=>true, 'data'=>$post->stubId ];
        return new JsonResponse($jsonData);

    }

    /** 
    * @Route("/api/v1/attribute/new")
    */ 
    public function attribute_new(Request $request)
    {
        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager();

        $attributeCategory = $entityManager->getRepository('App\Entity\AttributeCategory')->find(2); // Find a way to get this. Static right now

        $attribute = new Attribute();
        $attribute->setName($post->name);
        $attribute->setAttributeCategoryId($attributeCategory);
        $attribute->setIsVerified(false);
        $attribute->setCreatedAt(new \DateTime());
        $attribute->setUpdatedAt(new \DateTime());

        $entityManager->persist($attribute);
        $entityManager->flush();

        if($post->has_parent){

          if($post->category_selected==0 && $post->relationship_name!=''){
            /* Create New Relationship */
            $attributeRelationCategory = new AttributeRelationCategory();
            $attributeRelationCategory->setName($post->relationship_name);
            $entityManager->persist($attributeRelationCategory);
            $entityManager->flush();
          }else{
            /* Fetch Existing relationship */
            $attributeRelationCategory = $entityManager->getRepository('App\Entity\AttributeRelationCategory')->find($post->category_selected);
          }

          /* Set up the relationship */
          $attribute2 = $entityManager 
           ->getRepository('App\Entity\Attribute')
           ->find($post->parentAttributeId);

          $attributeRelation = new AttributeRelation();
          $attributeRelation->setAttributeId($attribute);
          $attributeRelation->setParentAttributeId($attribute2);
          $attributeRelation->setAttributeRelationCategoryId($attributeRelationCategory);
          $entityManager->persist($attributeRelation);
          $entityManager->flush();

        }

        $jsonData = ['success'=>true, 'data'=>$attribute->getAttributeId() ];
        return new JsonResponse($jsonData);

    }

    /** 
    * @Route("/api/v1/attribute/create_attribute")
    */ 
    public function create_attribute(Request $request)
    {

        $post = $request->getContent();
        $post = json_decode($post);

        $entityManager = $this->getDoctrine()->getManager(); 
        $attributes = $entityManager->getRepository('App\Entity\AttributeCategory')->findBy(
        [
            'stubCategoryId' => $post->stubCategoryId,
        ]);


        $relations = [];
        $categoryRelations = [];
        $categories_all = [];
        $data = [];
        $attribute_list = [];
        $attribute_list_keys = [];


        foreach ($attributes as $attribute) {
            $data[] = $attribute->getAttributeCategoryId();
        }

        $attributes = $entityManager->getRepository('App\Entity\Attribute')->createQueryBuilder('a')
            ->andWhere('a.attributeCategoryId IN ('.implode(',', $data).')')
            ->getQuery()->execute();

        $attribute_Ids = [];

        foreach ($attributes as $attribute) {
            $attribute_list[] = [
                'attributeId'=>$attribute->getAttributeId(),
                'name'=>$attribute->getName(),
                'description'=>$attribute->getDescription(),
            ];            
            $attribute_list_keys[$attribute->getAttributeId()] = [
                'name'=>$attribute->getName(),
            ];
            array_push($attribute_Ids,$attribute->getAttributeId());
        }


        $attributeRelation = $entityManager->getRepository('App\Entity\AttributeRelation')->createQueryBuilder('a')
        ->andWhere('a.parentAttributeId IN ('.implode(',', $attribute_Ids).')')
        ->getQuery()->execute();


        foreach ($attributeRelation as $key => $value) {
            $relations[] = [
              'attributeId'=>$value->getAttributeId()->getAttributeId(),
              'name'=>$attribute_list_keys[$value->getAttributeId()->getAttributeId()]['name'],
              'parentAttributeId'=>$value->getParentAttributeId()->getAttributeId(),
              'attributeRelationCategoryId'=>$value->getAttributeRelationCategoryId()->getAttributeRelationCategoryId(),
            ];
            array_push($categories_all,$value->getAttributeRelationCategoryId()->getAttributeRelationCategoryId());
        }

        $relations = $this->_group_by($relations,'parentAttributeId');

        if(count($categories_all)){

          $attributeRelationCategory = $entityManager->getRepository('App\Entity\AttributeRelationCategory')->createQueryBuilder('a')
            ->andWhere('a.attributeRelationCategoryId IN ('.implode(',', $categories_all).')')
          ->getQuery()->execute();

          foreach ($attributeRelationCategory as $key => $value) {
            $categoryRelations[$value->getAttributeRelationCategoryId()] = [
              'attributeRelationCategoryId'=>$value->getAttributeRelationCategoryId(),
              'name'=>$value->getName()
            ];
          }

        }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
        $jsonData = ['success'=>true,'attribute_list'=>$attribute_list,'relations'=>$relations,'categoryRelations'=>$categoryRelations];
        return new JsonResponse($jsonData);

    }




}

