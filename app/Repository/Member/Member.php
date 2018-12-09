<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 04.11.18
 * Time: 15:05
 */

namespace App\Repository\Member;

use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\ValueTypeException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Repository\Member\Field\DateField;
use App\Repository\Member\Field\Field;
use App\Repository\Member\Field\FieldFactory;
use App\Repository\Member\Field\LongTextField;
use App\Repository\Member\Field\Mapping\Loader;
use App\Repository\Member\Field\MultiSelectField;
use App\Repository\Member\Field\SelectField;
use App\Repository\Member\Field\TextField;

/**
 * Class Member
 *
 * Manages all member Properties. Provides magic access to the following
 * properties:
 *
 * @property null|int $id
 * @property null|Group[] $rootGroups
 * @property null|Group[] $groups
 * @property TextField $company
 * @property TextField $firstName
 * @property TextField $lastName
 * @property SelectField $recordCategory
 * @property SelectField $recordStatus
 * @property SelectField $language
 * @property SelectField $gender
 * @property SelectField $salutationFormal
 * @property SelectField $salutationInformal
 * @property TextField $title
 * @property TextField $address1
 * @property TextField $address2
 * @property TextField $zip
 * @property TextField $city
 * @property SelectField $country
 * @property SelectField $postStatus
 * @property TextField $email1
 * @property TextField $email2
 * @property SelectField $emailStatus
 * @property TextField $mobilePhone
 * @property TextField $landlinePhone
 * @property TextField $workPhone
 * @property SelectField $phoneStatus
 * @property DateField $birthday
 * @property TextField $website
 * @property TextField $facebook
 * @property TextField $twitter
 * @property TextField $iban
 * @property SelectField $coupleCategory
 * @property SelectField $partnerSalutationFormal
 * @property SelectField $partnerSalutationInformal
 * @property TextField $partnerFirstName
 * @property TextField $partnerLastName
 * @property SelectField $magazineCountryD
 * @property SelectField $magazineCountryF
 * @property SelectField $magazineCantonD
 * @property SelectField $magazineCantonF
 * @property SelectField $magazineMunicipality
 * @property TextField $magazineOther
 * @property SelectField $newsletterCountryD
 * @property SelectField $newsletterCountryF
 * @property SelectField $newsletterCantonD
 * @property SelectField $newsletterCantonF
 * @property SelectField $newsletterMunicipality
 * @property TextField $newsletterOther
 * @property SelectField $pressReleaseCountryD
 * @property SelectField $pressReleaseCountryF
 * @property SelectField $pressReleaseCantonD
 * @property SelectField $pressReleaseCantonF
 * @property SelectField $pressReleaseMunicipality
 * @property SelectField $memberStatusCountry
 * @property SelectField $memberStatusCanton
 * @property SelectField $memberStatusRegion
 * @property SelectField $memberStatusMunicipality
 * @property SelectField $memberStatusYoung
 * @property TextField $responsibility
 * @property TextField $entryChannel
 * @property DateField $membershipStart
 * @property DateField $membershipEnd
 * @property SelectField $membershipFeeCountry
 * @property SelectField $membershipFeeCanton
 * @property SelectField $membershipFeeRegion
 * @property SelectField $membershipFeeMunicipality
 * @property SelectField $membershipFeeYoung
 * @property MultiSelectField $interests
 * @property LongTextField $roleCountry
 * @property LongTextField $roleCanton
 * @property LongTextField $roleRegion
 * @property LongTextField $roleMunicipality
 * @property LongTextField $roleYoung
 * @property LongTextField $roleInternational
 * @property MultiSelectField $request
 * @property TextField $profession
 * @property SelectField $professionCategory
 * @property TextField $networkNpo
 * @property TextField $networkOther
 * @property MultiSelectField $mandateCountry
 * @property LongTextField $mandateCountryDetail
 * @property MultiSelectField $mandateCanton
 * @property LongTextField $mandateCantonDetail
 * @property MultiSelectField $mandateRegion
 * @property LongTextField $mandateRegionDetail
 * @property MultiSelectField $mandateMunicipality
 * @property LongTextField $mandateMunicipalityDetail
 * @property SelectField $donorCountry
 * @property SelectField $donorCanton
 * @property SelectField $donorRegion
 * @property SelectField $donorCommune
 * @property SelectField $donorYoung
 * @property LongTextField $notesCountry
 * @property LongTextField $notesCanton
 * @property LongTextField $notesMunicipality
 * @property LongTextField $legacy
 *
 * @package App\Repository\Member
 */
class Member {
	/**
	 * The fields with the member data
	 *
	 * @var array
	 */
	private $fields = [];
	
	/**
	 * Alias to the $fields field, but using the webling key as key
	 *
	 * @var array
	 */
	private $fieldsByWeblingKey;
	
	/**
	 * The groups the member belongs to
	 *
	 * @var null|Group[]
	 */
	private $groups;
	
	/**
	 * The id of the member in webling
	 *
	 * @var int|null
	 */
	private $id;
	
	/**
	 * Member constructor.
	 *
	 * NOTE: To prevent accidental overwriting of MultiSelect fields, this
	 * must be explicitly allowed.
	 *
	 * @param array $data with either of the following structure:
	 *                    variant 1: $data[][key] = value
	 *                    variant 2: $data[][key] = weblingValue
	 *                    variant 3: $data[][weblingKey] = value
	 *                    variant 4: $data[][weblingKey] = weblingValue
	 * @param int|null $id the id in webling
	 * @param Groups[] $groups
	 * @param bool $allowSettingMultiSelectFields
	 *
	 * @throws MultiSelectOverwriteException
	 * @throws WeblingFieldMappingConfigException
	 * @throws InvalidFixedValueException
	 * @throws ValueTypeException
	 * @throws MemberUnknownFieldException
	 */
	public function __construct(
		array $data = [],
		int $id = null,
		array $groups = null,
		bool $allowSettingMultiSelectFields = false
	) {
		$this->id     = $id;
		$this->groups = $groups;
		
		// create fields from given data
		foreach ( $data as $key => $value ) {
			$field = FieldFactory::create( $key );
			
			if ( ! $field ) {
				// handle the 'Skip' field type
				continue;
			}
			
			// throw error if a MultiSelect value should be set and this is not
			// explicitly allowed
			if ( $field instanceof MultiSelectField
			     && ! $allowSettingMultiSelectFields
			     && $value !== null
			) {
				throw new MultiSelectOverwriteException( 'The initialisation of members with MultiSelectFields must explicitly be allowed to prevent accidental overwrite of existing values.' );
			}
			
			// as we set the value after creating the field, make sure the field
			// is yet not marked dirty
			$field->setValue( $value, false );
			
			$internalKey = $field->getKey();
			$weblingKey  = $field->getWeblingKey();
			
			$this->fields[ $internalKey ]            = $field;
			$this->fieldsByWeblingKey[ $weblingKey ] = &$this->fields[ $internalKey ];
		}
		
		// create other fields
		$setFields = array_keys( $this->fields );
		foreach ( Loader::getInstance()->getFieldKeys() as $key ) {
			if ( ! in_array( $key, $setFields ) ) {
				$field = FieldFactory::create( $key );
				if ( $field ) {
					// handle Skip field type
					$this->fields[ $key ] = $field;
				}
			}
		}
	}
	
	/**
	 * Magic access to member properties.
	 *
	 * @param $name
	 *
	 * @return null|int|Group[]|Field
	 *
	 * @throws MemberUnknownFieldException
	 */
	public function __get( $name ) {
		if ( 'id' === $name ) {
			return $this->id;
		}
		
		if ( 'groups' === $name ) {
			return $this->groups;
		}
		
		if ( 'rootGroups' === $name ) {
			return $this->getRootGroups();
		}
		
		return $this->getField( $name );
	}
	
	/**
	 * Returns an array with the root groups of this member.
	 *
	 * Access this function using magic property access.
	 *
	 * @return Group[]
	 */
	private function getRootGroups() {
		// todo: implement this
		// make sure to filter duplicate root groups
	}
	
	/**
	 * Return field by internal key or by webling key.
	 *
	 * @param string $name
	 *
	 * @return Field
	 *
	 * @throws MemberUnknownFieldException
	 */
	public function getField( string $name ): Field {
		if ( array_key_exists( $name, $this->fields ) ) {
			return $this->fields[ $name ];
		}
		
		if ( array_key_exists( $name, $this->fieldsByWeblingKey ) ) {
			return $this->fieldsByWeblingKey[ $name ];
		}
		
		$trace = debug_backtrace();
		throw new MemberUnknownFieldException( "Tried to access undefined field: {$name} in {$trace[0]['file']} on line {$trace[0]['line']}" );
	}
	
	/**
	 * Return all fields in an array.
	 *
	 * @return array
	 */
	public function getFields(): array {
		return $this->fields;
	}
	
	/**
	 * Return only the dirty fields in an array.
	 *
	 * @return array
	 */
	public function getDirtyFields(): array {
		$dirty = [];
		
		/** @var Field $field */
		foreach ( $this->fields as $field ) {
			if ( $field->isDirty() ) {
				$dirty[] = $field;
			}
		}
		
		return $dirty;
	}
	
	
}
