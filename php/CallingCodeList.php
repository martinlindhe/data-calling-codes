<?php namespace MartinLindhe\Data\CallingCodes;

class CallingCodeList
{
    /**
     * @return CallingCode[]
     */
    public static function all()
    {
        $fileName = __DIR__.'/../data/calling_codes.json';

        $data = file_get_contents($fileName);

        $list = [];
        foreach (json_decode($data) as $t) {
            $o = new CallingCode;
            foreach ($t as $key => $value) {
                $o->{$key} = $value;
            }
            $list[] = $o;
        }
        return $list;
    }
}
