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
use App\Repository\Group\Group;
use App\Repository\Group\GroupRepository;
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
	public const KEY_ID = 'id';
	public const KEY_GROUPS = 'groups';

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
	private $groups = null;

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
	 * @param Group[] $groups
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
		bool $allowSettingMultiSelectFields = null
	) {
		$this->id = $id;

		if ( is_array( $groups ) ) {
			$this->addGroups( $groups );
		}

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
	 * Make sure the none primitive members properties get cloned instead of shallow copied
	 */
	public function __clone() {
		$fields = get_object_vars( $this );
		foreach ( $fields as $fieldKey => $fieldValue ) {
			if ( is_object( $fieldValue ) ) {
				$this->$fieldKey = clone $fieldValue;
			} else if ( is_array( $fieldValue ) ) {
				foreach ( $fieldValue as $arrayFieldKey => $arrayFieldValue ) {
					if ( is_object( $arrayFieldValue ) ) {
						unset( $this->$fieldKey[ $arrayFieldKey ] ); // yes, this line is necessary!
						$this->$fieldKey[ $arrayFieldKey ] = clone $arrayFieldValue;
					}
					// implement support for multidimensional arrays here, if needed.
				}
			}
		}
	}

	/**
	 * Add a single or multiple groups to this member. Duplicates impossible.
	 *
	 * @param Group|Group[] $groups
	 */
	public function addGroups( $groups ) {
		$groups = is_array( $groups ) ? $groups : [ $groups ];

		foreach ( $groups as $group ) {
			$this->groups[ $group->getId() ] = $group;
		}
	}

	/**
	 * Remove a single or multiple groups from this member.
	 *
	 * @param Group|Group[] $groups
	 */
	public function removeGroups( $groups ) {
		$groups = is_array( $groups ) ? $groups : [ $groups ];

		foreach ( $groups as $group ) {
			unset( $this->groups[ $group->getId() ] );
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
		if ( self::KEY_ID === $name ) {
			return $this->id;
		}

		if ( self::KEY_GROUPS === $name ) {
			return $this->groups;
		}

		return $this->getField( $name );
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
	 * Return an array with the ids of the first groups below the given root group.
	 *
	 * @param int $rootGroupId the id of the group that should be considered to
	 *                         be the root group.
	 *
	 * @return int[]
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \Webling\API\ClientException
	 */
	public function getFirstLevelGroupIds( int $rootGroupId ): array {
		$rootPaths = $this->getRootPaths();

		if ( empty( $rootPaths ) ) {
			return [];
		}

		$rootGroups = [];
		foreach ( $rootPaths as $groupId => $path ) {
			if ( empty( $path ) ) {
				continue;
			}

			$rootGroupKey = array_search( $rootGroupId, $path );

			// discard other branches
			if ( false === $rootGroupKey ) {
				continue;
			}

			// get id of first level group
			$firstLevelGroupId = null;
			if ( ! isset( $path[ $rootGroupKey + 1 ] ) ) {
				// the group itself is the first level group
				$firstLevelGroupId = $groupId;
			} else {
				// get first level group from root path
				$firstLevelGroupId = $path[ $rootGroupKey + 1 ];
			}

			// prevent duplicates
			if ( ! in_array( $firstLevelGroupId, $rootGroups ) ) {
				$rootGroups[] = $firstLevelGroupId;
			}
		}

		return $rootGroups;
	}

	/**
	 * Return array with group paths
	 *
	 * @return int[][]
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \Webling\API\ClientException
	 */
	public function getRootPaths(): array {
		if ( empty( $this->groups ) ) {
			return [];
		}

		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );

		$rootPaths = [];
		foreach ( $this->groups as $group ) {
			$rootPaths[ $group->getId() ] = $group->getRootPath( $groupRepository );
		}

		return $rootPaths;
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

	/**
	 * Check if this member is in the given group or in one of its subgroups.
	 *
	 * @param Group $group
	 *
	 * @return bool
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \Webling\API\ClientException
	 */
	public function isDescendantOf( Group $group ): bool {
		if ( in_array( $group, $this->groups ) ) {
			return true;
		}

		$rootPaths = $this->getRootPaths();

		foreach ( $rootPaths as $path ) {
			if ( in_array( $group->getId(), $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the ids of this member's groups
	 *
	 * @return int[]
	 */
	public function getGroupIds(): array {
		return array_keys( (array) $this->groups );
	}

	/**
	 * Make sure the member only in the given groups
	 *
	 * @param Group|Group[] $groups
	 */
	public function setGroups( $groups ) {
		$this->removeGroups( (array) $this->groups );
		$this->addGroups( $groups );
	}
}
