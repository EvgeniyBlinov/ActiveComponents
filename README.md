ActiveComponents
================

Light ORM for ZF2


## How to use ActiveRecordModel ##

Create a model entity.
You can do this with your own model entity generator.

```php
namespace ModuleName\modelEntity;

class UsersModelEntity extends \ActiveComponents\ActiveRecordModel

    public function rules()
    {
        return array(array(implode(',',$this->attributeNames()),'match','pattern'=>'/(.*)/'));
    }

    public function getTableName()
    {
        return "users";
    }

    public function attributeNames()
    {
        return array(
            "id",
            "email",
            "username",
            "surname",
            "image",
            "phone",
            "isq",
            "skype",
            "password",
            "status",
            "deleted",
            "registration_date",
            "authorized_date",
            "send_email",
            "role",
        );
    }

} 
```


UsersModel extends UsersModelEntity.

```php
<?php
namespace ModuleName\Models;


use ModuleName\modelEntity\UsersModelEntity;

class UsersModel extends UsersModelEntity
{
    const STATUS_ACTIVE     = 1;
    const STATUS_NOT_ACTIVE = 0;

    /**
     * Create statically model
     * @return ActiveRecordModel
     */
    public static function model($options = null, $className=__CLASS__)
    {
        return parent::model($options, $className);
    }

    /**
     * @return array of rules
     */
    public function rules()
    {
        return array(
            array('username, surname', 'match', 'pattern' => '/^[-_0-9a-zA-Z]*$/iu'),
            array('username', 'required'),
            array('email', 'email'),
        );
    }
    
//////////////// ADVANCED FUNCTIONS ////////////////////////////////////////////

    /**
     * Before validate function
     * @return boolean
     */
    public function beforeValidate($rules = array())
    {
        return !$this->hasErrors();
    }
    
    /**
     * Get concat name
     * @return \Zend\Db\Sql\Expression
     */
    public function getConcatName()
    {
       return new \Zend\Db\Sql\Expression('CONCAT_WS(" ",`users`.`username`,`users`.`surname`)');
    }

    /**
     * Scope get student on course
     * @param integer $id_course
     * @param integer $id_curator
     * return array of criteria
     */
    public function scopeGetStudentOnCourse($id_course, $id_curator = 0)
    {
        //get a select object
        $select = $this->sql->select();

        $this->setCriteria(array(
            'columns' => array(array(
                'name'     => $this->getConcatName(),
                'username' => 'username',
                'surname'  => 'surname',
                'email'    => 'email',
                'deleted'  => 'deleted',
                'id_a'     => 'id',
                'status',
                'authorized_date',
            )),
            
            // join `progress` table
            'multijoin' => array(
                array(
                    // progress as p
                    array( 'p' => 'progress' ),
                    // ON p.id_student= users.id
                    'p.id_student= users.id',
                    // columns aliases
                    array(
                        'status_p' => 'status',
                        'id_student'
                    ),
                    // LEFT OUTER JOIN
                    $select::JOIN_LEFT . ' ' . $select::JOIN_OUTER
                ),
            ),
            
            // WHERE
            'where' => array(array(
                'p.id_course' => $id_course,
            )),
            
            //'group' => 'table.column',
            //'order' => 'ASC column'
        ));
        
        // if some parameter != 0, join another table
        if ($id_curator != 0) {
            $this->setCriteria(array(
                'multijoin' => array(
                    array(
                        // table alias
                        array('sc' => 'student_curator'),
                        // table condition as SQL expression
                        new \Zend\Db\Sql\Expression('sc.id_student=p.id_student AND sc.id_curator=' . $id_curator . ' AND sc.id_course=p.id_course'),
                        // columns aliases
                        array()
                    )
                )
            ));
        }

        // return Criteria
        return $this->getCriteria();
    }

    /**
     * Get student on course
     * @param integer $id_course
     * @param integer $id_curator
     * return mixed
     */
    public function getStudentOnCourse($id_course, $id_curator=0)
    {
        // ...some code
        
        $criteria = $this->scopeGetStudentOnCourse($id_course, $id_curator);
        
        // some modifications
        $criteria['where'][] = array(
            'users.deleted' => '0',
            'users.status'  => '1'
        );

        // set criteria
        $this->setCriteria($criteria,false);
        
        // return result as object if find only one record or array
        return $this->findByCriteria();
    }

}
```

Usage:
`\ModuleName\Models\UsersModel::model()` - get a model.
`\ModuleName\Models\UsersModel::model()->setAttributes(array('name' => 'Sonya'))->save()` - save a new record.
`\ModuleName\Models\UsersModel::model()->findByPk(1)` - UsersModel->tablePrimaryKey = 'id'; get record where id = 1
`\ModuleName\Models\UsersModel::model()->setAttributes(array('id' => 1))->findByCriteria()` - get record where id = 1
`\ModuleName\Models\UsersModel::model(array('asArray' => true))->setAttributes(array('id' => 1))->findByCriteria()` - get record as array where id = 1
    
    
