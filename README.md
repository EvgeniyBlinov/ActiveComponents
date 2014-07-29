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
