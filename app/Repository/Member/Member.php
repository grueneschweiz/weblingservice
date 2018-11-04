<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 04.11.18
 * Time: 15:05
 */

namespace App\Repository\Member;

use App\Exceptions\UnknownFieldException;
use App\Repository\Member\Field\DateField;
use App\Repository\Member\Field\Field;
use App\Repository\Member\Field\LongTextField;
use App\Repository\Member\Field\MultiSelectField;
use App\Repository\Member\Field\SelectField;
use App\Repository\Member\Field\TextField;

/**
 * Class Member
 *
 * Manages all member Properties. Provides magic access to the following
 * properties:
 *
 * @property int $id
 * @property Group $rootGroup
 * @property Group[] $groups
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
	private $fields;
	private $groups;
	private $rootGroup;
	private $id;
	
	/**
	 * Member constructor.
	 *
	 * @param array $data with ether of the following structure:
	 *                    variant 1: $data[][key] = value
	 *                    variant 2: $data[][key] = weblingValue
	 *                    variant 3: $data[][weblingKey] = value
	 *                    variant 4: $data[][weblingKey] = weblingValue
	 * @param int|null $id the id in webling
	 * @param Groups[] $groups
	 */
	public function __construct( array $data = null, int $id = null, array $groups = null ) {
		// todo: implement it
		// make sure to construct all fields regardless of the input
		// make sure to set the root group from the given groups
	}
	
	/**
	 * Magic access to member properties.
	 *
	 * NOTE: Fields may only be accessed using the internal key.
	 *
	 * @param $name
	 *
	 * @throws UnknownFieldException
	 */
	public function __get( $name ) {
	
	}
	
	/**
	 * Return field by internal key or by webling key.
	 *
	 * @param string $name
	 *
	 * @return Field
	 *
	 * @throws UnknownFieldException
	 */
	public function getField( string $name ): Field {
	
	}
	
	/**
	 * Return all fields in an array.
	 *
	 * @return array
	 */
	public function getFields(): array {
	
	}
	
	/**
	 * Return only the dirty fields in an array.
	 *
	 * @return array
	 */
	public function getDirtyFields(): array {
	
	}
}
