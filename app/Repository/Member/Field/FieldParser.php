<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 07.06.17
 * Time: 17:51
 */

namespace App\Model\Webling\Field;

use App\Exceptions\WeblingFieldParsingException;
use App\Exceptions\WeblingUnknownFieldKeyException;
use App\Model\Behavior\WeblingBehavior;

class FieldParser
{
    /**
     * @var array
     */
    private $rawdata;

    /**
     * @var array
     */
    private $definitions;

    /**
     * @var WeblingBehavior the connection object
     */
    private $conn;

    /**
     * FieldParser constructor.
     *
     * @param $data array with the raw data
     * @param $definitions array preset the definitions
     * @param WeblingBehavior $connection the connection object
     */
    public function __construct(array $data, array $definitions, $connection)
    {
        $this->rawdata = $data;
        $this->definitions = $definitions;
        $this->conn = $connection;
    }

    /**
     * Return a field from given $fieldId (webling name or internal name) or null if $fieldId doesn't exist in rawdata
     *
     * @param string $fieldId webling field name or internal field name
     *
     * @return Field|Null
     *
     * @throws WeblingFieldParsingException if the given data type is unknown
     */
    public function parse(string $fieldId)
    {
        // return null if we dont have any data
        if (!array_key_exists($fieldId, $this->rawdata)) {
            return null;
        }

        // get data
        $value = $this->rawdata[$fieldId];

        // make sure we've got the webling field name
        try {
            $fieldId = $this->conn->getWeblingFieldName($fieldId);
        } catch (WeblingUnknownFieldKeyException $exception) {
        }

        // make sure we've got definitions for this field
        if (!array_key_exists($fieldId, $this->definitions)) {
            throw new WeblingFieldParsingException("Field '{$fieldId}' not fount in definitions.");
        }

        // if its the id, just return a text field
        if ($this->conn->getWeblingFieldName('mitglieder_id') === $fieldId) {
            return new TextField('mitglieder_id', $this->rawdata[$fieldId]);
        }

        // get the internal field name
        $internalId = $this->conn->getInternalFieldName($fieldId);


        switch ($this->definitions[$fieldId]['datatype']) {
            case 'longtext':
                return new LongTextField($internalId, $value);
                break;

            case 'text':
                return new TextField($internalId, $value);
                break;

            case 'date':
                return new DateField($internalId, $value);
                break;

            case 'autoincrement':
                return new TextField($internalId, $value);
                break;

            case 'enum':
                $s = new SelectField($internalId);
                $s->setPossibleValues($this->definitions[$fieldId]['values']);
                $s->setValue($value, false);

                return $s;
                break;

            case 'multienum':
                $m = new MultiSelectField($internalId);
                $m->setPossibleValues($this->definitions[$fieldId]['values']);
                $m->setValue((array)$value, false);

                return $m;
                break;

            default:
                throw new WeblingFieldParsingException('Unknown data type');
        }
    }
}