ActiveComponents
================

Light ORM for ZF2



```php
namespace Training\modelEntity;

class UsersModelEntity extends \Training\Components\TrainingActiveRecordModel 
{

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
