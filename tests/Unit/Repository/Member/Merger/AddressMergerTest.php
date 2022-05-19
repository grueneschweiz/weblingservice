<?php /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace App\Repository\Member\Merger;

use App\Repository\Member\Field\FieldFactory;
use App\Repository\Member\Member;
use ReflectionMethod;
use Tests\TestCase;

class AddressMergerTest extends TestCase
{
    
    /**
     * @dataProvider provideSuccess
     */
    public function testMerge__success(
        string  $fieldKey,
        array   $dstMemberData,
        array   $srcMemberData,
        ?string $result
    ): void
    {
        $dstMember = new Member($dstMemberData);
        $srcMember = new Member($srcMemberData);
        
        $dst = $dstMember->$fieldKey;
        $src = $srcMember->$fieldKey;
        
        $merger = new AddressMerger($dst, $src, $dstMember, $srcMember);
        
        self::assertTrue($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideSuccess(): array
    {
        $someAddress = self::getSomeAddress();
        
        $fields = ['address1', 'address2', 'zip', 'city'];
        
        $cases__srcEmpty = [];
        $cases__dstEmpty = [];
        $cases__similar = [];
        foreach ($fields as $field) {
            $cases__srcEmpty["{$field}__srcEmpty"] = [
                $field,
                [...$someAddress, $field => 'some value'],
                [],
                'some value'
            ];
            $cases__dstEmpty["{$field}__dstEmpty"] = [
                $field,
                [],
                [...$someAddress, $field => 'some value'],
                'some value'
            ];
            $cases__similar["{$field}__similar"] = [
                $field,
                [...$someAddress, $field => 'some value'],
                [...$someAddress, $field => strtoupper('some value')],
                'some value'
            ];
        }
    
        $completeAddress1__similarAddress2 = [
            'address1',
            [...$someAddress, 'address1' => null, 'address2' => 'Postfach 123'],
            [...$someAddress, 'address1' => 'Dorf 1', 'address2' => 'Postfach'],
            'Dorf 1'
        ];
    
        $completeAddress1__emptyAddress2 = [
            'address1',
            [...$someAddress, 'address1' => null, 'address2' => null],
            [...$someAddress, 'address1' => 'Dorf 1', 'address2' => null],
            'Dorf 1'
        ];
    
        $completeAddress2__similarAddress1 = [
            'address2',
            [...$someAddress, 'address1' => 'dorfstr 1', 'address2' => null],
            [...$someAddress, 'address1' => 'Dorfstrasse 1', 'address2' => 'Postfach'],
            'Postfach'
        ];
    
        $completeAddress2__emptyAddress1 = [
            'address2',
            [...$someAddress, 'address1' => null, 'address2' => null],
            [...$someAddress, 'address1' => null, 'address2' => 'Postfach'],
            'Postfach'
        ];
        
        $completeZip__similarCity = [
            'zip',
            [...$someAddress, 'zip' => null, 'city' => 'Wald'],
            [...$someAddress, 'zip' => '8543', 'city' => 'wald'],
            '8543'
        ];
    
        $completeZip__emptyCity = [
            'zip',
            [...$someAddress, 'zip' => null, 'city' => null],
            [...$someAddress, 'zip' => '8543', 'city' => null],
            '8543'
        ];
    
        $completeCity__similarZip = [
            'city',
            [...$someAddress, 'zip' => '1234', 'city' => null],
            [...$someAddress, 'zip' => '1234', 'city' => 'wald'],
            'wald'
        ];
    
        $completeCity__emptyZip = [
            'city',
            [...$someAddress, 'zip' => null, 'city' => null],
            [...$someAddress, 'zip' => null, 'city' => 'wald'],
            'wald'
        ];
    
        $completeCountry = [
            'country',
            [...$someAddress, 'country' => null],
            [...$someAddress, 'country' => 'ch'],
            'ch'
        ];
        
        $updateInvalid__newTown = [
            'postStatus',
            [...$someAddress, 'postStatus' => 'invalid'],
            [...$someAddress, 'city' => 'new town', 'postStatus' => 'active'],
            'active'
        ];
    
        $updateInvalid__completeAddress1 = [
            'postStatus',
            [...$someAddress, 'address1' => null, 'postStatus' => 'invalid'],
            [...$someAddress, 'address1' => 'Dorfstr. 1', 'postStatus' => 'active'],
            'active'
        ];
    
        $updateInvalid__completeAddress2 = [
            'postStatus',
            [...$someAddress, 'address2' => null, 'postStatus' => 'invalid'],
            [...$someAddress, 'address2' => 'c/o Peter Müller', 'postStatus' => 'active'],
            'active'
        ];
        
        $updateInvalid__noChange = [
            'postStatus',
            [...$someAddress, 'postStatus' => 'invalid'],
            [...$someAddress, 'postStatus' => 'active'],
            'invalid'
        ];
        
        $updateInvalid__countryOnly = [
            'postStatus',
            [...$someAddress, 'country' => null, 'postStatus' => 'invalid'],
            [...$someAddress, 'country' => 'CH', 'postStatus' => 'active'],
            'invalid'
        ];
        
        return [
            ...$cases__srcEmpty,
            ...$cases__dstEmpty,
            ...$cases__similar,
            'completeAddress1__similarAddress2' => $completeAddress1__similarAddress2,
            'completeAddress1__emptyAddress2' => $completeAddress1__emptyAddress2,
            'completeAddress2__similarAddress1' => $completeAddress2__similarAddress1,
            'completeAddress2__emptyAddress1' => $completeAddress2__emptyAddress1,
            'completeZip__similarCity' => $completeZip__similarCity,
            'completeZip__emptyCity' => $completeZip__emptyCity,
            'completeCity__similarZip' => $completeCity__similarZip,
            'completeCity__emptyZip' => $completeCity__emptyZip,
            'completeCountry' => $completeCountry,
            'updateInvalid__newTown' => $updateInvalid__newTown,
            'updateInvalid__completeAddress1' => $updateInvalid__completeAddress1,
            'updateInvalid__completeAddress2' => $updateInvalid__completeAddress2,
            'updateInvalid__noChange' => $updateInvalid__noChange,
            'updateInvalid__countryOnly' => $updateInvalid__countryOnly,
        ];
    }
    
    /**
     * @dataProvider provideError
     */
    public function testMerge__error(
        string  $fieldKey,
        array   $dstMemberData,
        array   $srcMemberData,
        ?string $result
    ): void
    {
        $dstMember = new Member($dstMemberData);
        $srcMember = new Member($srcMemberData);
        
        $dst = $dstMember->$fieldKey;
        $src = $srcMember->$fieldKey;
        
        $merger = new AddressMerger($dst, $src, $dstMember, $srcMember);
        
        self::assertFalse($merger->merge());
        self::assertEquals($result, $dst->getValue());
    }
    
    public function provideError(): array
    {
        $someAddress = self::getSomeAddress();
        
        $fields = ['address1', 'address2', 'zip', 'city'];
        
        $cases__conflict = [];
        $cases__completeInvalid = [];
        foreach ($fields as $field) {
            $cases__conflict["{$field}__conflict"] = [
                $field,
                $someAddress,
                [...$someAddress, 'address1' => 'some other value'],
                $someAddress[$field]
            ];
            $cases__completeInvalid["{$field}__completeInvalid"] = [
                $field,
                $someAddress,
                [...$someAddress, $field => 'some other value', 'postStatus' => 'invalid'],
                $someAddress[$field]
            ];
        }
    
        $completeAddress1__butAlreadyInAddress2 = [
            'address1',
            [...$someAddress, 'address1' => null, 'address2' => 'dorfstr 1'],
            [...$someAddress, 'address1' => 'Dorfstrasse 1', 'address2' => null],
            null
        ];
    
        $completeAddress2__differentAddress1 = [
            'address2',
            [...$someAddress, 'address1' => 'Müllerweg 5', 'address2' => null],
            [...$someAddress, 'address1' => 'Dorfstrasse 1', 'address2' => 'Postfach 999'],
            null
        ];
        
        $completeZip__differentCity = [
            'zip',
            [...$someAddress, 'zip' => null, 'city' => 'wald'],
            [...$someAddress, 'zip' => '5678', 'city' => 'dorf'],
            null
        ];
    
        $completeCity__differentZip = [
            'city',
            [...$someAddress, 'zip' => '1111', 'city' => null],
            [...$someAddress, 'zip' => '9999', 'city' => 'dorf'],
            null
        ];
    
        $updateUnwanted = [
            'postStatus',
            [...$someAddress, 'postStatus' => 'unwanted'],
            [...$someAddress, 'address1' => 'some other street', 'postStatus' => 'active'],
            'unwanted'
        ];
        
        return [
            ...$cases__conflict,
            ...$cases__completeInvalid,
            'completeAddress2__differentAddress1' => $completeAddress2__differentAddress1,
            'completeAddress1__butAlreadyInAddress2' => $completeAddress1__butAlreadyInAddress2,
            'completeZip__differentCity' => $completeZip__differentCity,
            'completeCity__differentZip' => $completeCity__differentZip,
            'updateUnwanted' => $updateUnwanted,
        ];
    }
    
    /**
     * @dataProvider provideRemoveWordStreet
     */
    public function testRemoveWordStreet(string $input, string $expected): void
    {
        $method = new ReflectionMethod(AddressMerger::class, 'removeWordStreet');
        
        $actual = $method->invoke(null, $input);
        
        self::assertEquals($expected, $actual);
    }
    
    public function provideRemoveWordStreet(): array
    {
        return [
            ['Chemin Mestrezat 25A', 'Mestrezat 25A'],
            ['Ch Mestrezat 25A', 'Mestrezat 25A'],
            ['Ch. Mestrezat 25A', 'Mestrezat 25A'],
            ['Route de l’Eglise 36', 'Eglise 36'],
            ['Rte de l’Eglise 36', 'Eglise 36'],
            ['Rte. de l’Eglise 36', 'Eglise 36'],
            ['Avenue Jaques-Martin 1', 'Jaques-Martin 1'],
            ['Av Jaques-Martin 1', 'Jaques-Martin 1'],
            ['Av. Jaques-Martin 1', 'Jaques-Martin 1'],
            ['999, Boulevard Lumumba', '999, Lumumba'],
            ['999, Boul Lumumba', '999, Lumumba'],
            ['999, Boul. Lumumba', '999, Lumumba'],
            ['999, Bd Lumumba', '999, Lumumba'],
            ['999, Bd. Lumumba', '999, Lumumba'],
            ['Place des Charmilles 9', 'Charmilles 9'],
            ['Pl des Charmilles 9', 'Charmilles 9'],
            ['Pl. des Charmilles 9', 'Charmilles 9'],
            ["Ruelle de l'Aurore 16", 'Aurore 16'],
            ['Quai du Cheval-Blanc 2', 'Cheval-Blanc 2'],
            ['Quai des Cheval-Blanc 3', 'Cheval-Blanc 3'],
            ['Quai de la Cheval-Blanc 3', 'Cheval-Blanc 3'],
            ['Hauptstrasse 1', 'Haupt 1'],
            ['Hauptstr. 1', 'Haupt 1'],
            ['Hauptstr 1', 'Haupt 1'],
            ['Kollerweg 11', 'Koller 11'],
            ['Wagenplatz 7', 'Wagen 7'],
            ['Goldgasse 55', 'Gold 55'],
            ['Goldgässlein 55', 'Gold 55'],
        ];
    }
    
    /**
     * @dataProvider provideFindAddressNumber
     */
    public function testFindAddressNumber(string $input, ?string $expected): void
    {
        $method = new ReflectionMethod(AddressMerger::class, 'findAddressNumber');
        
        $actual = $method->invoke(null, $input);
        
        self::assertEquals($expected, $actual);
    }
    
    public function provideFindAddressNumber(): array
    {
        return [
            ['Hauptstrasse 1', '1'],
            ['Hauptstrasse 1a', '1a'],
            ['999, Bd Lumumba', '999'],
            ['999 Bd Lumumba', '999'],
            ['Avenue du 1er Mars 21', '21'],
            ['22 Avenue du 1er Mars', '22'],
            ['23 Avenue de l´année 1945', '23'],
            ['Avenue de l´année 1945 24', '24'],
            ['Im Dorf', null],
        ];
    }
    
    /**
     * @dataProvider provideIsPOBox
     */
    public function testIsPOBox(string $input, bool $expected): void
    {
        $method = new ReflectionMethod(AddressMerger::class, 'isPOBox');
        
        $actual = $method->invoke(null, $input);
        
        self::assertEquals($expected, $actual);
    }
    
    public function provideIsPOBox(): array
    {
        return [
            ['Postfach', true],
            ['Postfach 123', true],
            ['Postf. 123', true],
            ['Postf 123', true],
            ['PF. 123', true],
            ['PF', true],
            ['Pfauenweg', false],
            ['Napf', false],
            ['case postale', true],
            ['case postale 2', true],
            ['cp. 2', true],
            ['cp 2', true],
            ['blabla cp 2', false],
            ['boite postale', true],
            ['boite postale 3', true],
            ['boîte postale', true],
            ['bp.', true],
            ['bp', true],
            ['bp asdf', false],
            ['asdf bp', false],
            ['casella postale', true],
            ['casella postale 4', true],
            ['cp. 4', true],
            ['cp 4', true],
            ['asdf cp 4', false],
            ['cp asdf', false],
            ['post office box', true],
            ['post office box 5', true],
            ['po box', true],
            ['pobox', true],
            ['p.o.box', true],
            ['p.o. box', true],
            ['po.box', true],
            ['po. box', true],
            ['po 5', true],
            ['p.o. 5', true],
            ['po. 5', true],
            ['po asdf', false],
            ['asdf po', false],
        ];
    }
    
    /**
     * @dataProvider provideAddressFieldsAreSimilar
     */
    public function testAddressFieldsAreSimilar(?string $value1, ?string $value2, bool $expected): void
    {
        $method = new ReflectionMethod(AddressMerger::class, 'addressLineIsSimilar');
        
        $field1 = FieldFactory::create('address1', $value1);
        $field2 = FieldFactory::create('address1', $value2);
        $actual = $method->invoke(null, $field1, $field2);
        
        self::assertEquals($expected, $actual);
    }
    
    public function provideAddressFieldsAreSimilar(): array
    {
        return [
            ['Chemin Mestrezat 25A', 'Ch. Mestrezat 25A', true],
            ['Route de l’Eglise 36', '36, rte de l’Eglise', true],
            ["Ruelle de l'Aurore 16", 'Rue de l´Aurore 16', true],
            ['Quai du Cheval-Blanc 2', 'Cheval-Blanc 2', true],
            ['Hauptstrasse 1', 'Hauptweg 1', true],
            ['  Hauptstrasse 1', 'Hauptstrasse 1', true],
            ['Hauptstrasse   1', 'Hauptstrasse 1', true],
            ['Hauptstrasse 1  ', 'Hauptstrasse 1', true],
            ['hauptstrasse 1  ', 'HAUPTSTRASSE 1', true],
            ['Hauptstrasse 1', 'Dorfstrasse 1', false],
            ['Hauptstrasse 1', 'Hauptstrasse 2', false],
            ['Hauptstrasse 1', 'Hauptstrasse 1A', false],
            ['Hauptstrasse 1', 'Hauptstrasse', false],
            ['Postfach', 'Postfach 123', true],
            ['Postfach 123', 'Postfach', true],
            ['Postfach', 'Pf', true],
            ['Casella Postale 2', 'Postfach 2', true],
            ['pf 1', 'pf 2', false],
        ];
    }
    
    /**
     * @dataProvider provideFieldsAreSimilarOrOneIsEmpty
     */
    public function testFieldsAreSimilarOrOneIsEmpty(?string $value1, ?string $value2, bool $expected): void
    {
        $method = new ReflectionMethod(AddressMerger::class, 'fieldsAreSimilarOrOneIsEmpty');
        
        $field1 = FieldFactory::create('firstName', $value1);
        $field2 = FieldFactory::create('firstName', $value2);
        $actual = $method->invoke(null, $field1, $field2);
        
        self::assertEquals($expected, $actual);
    }
    
    public function provideFieldsAreSimilarOrOneIsEmpty(): array
    {
        return [
            ['hans', 'HANS', true],
            ['  hans', 'hans', true],
            ['hans  ', 'hans', true],
            ['hans peter', 'hans   peter', true],
            ['hans', null, true],
            [null, 'hans', true],
            ['peter', 'hans', false],
            ['hans peter', 'hans', false],
            ['hanspeter', 'hans', false],
        ];
    }
    
    /**
     * @dataProvider provideIsAddressEmpty
     */
    public function testIsAddressEmpty(array $memberFields, bool $expected): void
    {
        $method = new ReflectionMethod(AddressMerger::class, 'isAddressEmpty');
        
        $member = new Member($memberFields);
        
        $actual = $method->invoke(null, $member);
        
        self::assertEquals($expected, $actual);
    }
    
    public function provideIsAddressEmpty(): array
    {
        return [
            [[], true],
            [['address1' => null], true],
            [['address1' => ''], true],
            [['address1' => '    '], true],
            [['address1' => 'Dorfstr. 1'], false],
            [['address2' => 'Postfach'], false],
            [['zip' => '8888'], false],
            [['city' => 'Zurigo'], false],
            [['country' => 'CH'], true],
            [['firstName' => 'Hans'], true],
        ];
    }
    
    /**
     * @dataProvider provideWholeAddressIsSimilar
     */
    public function testWholeAddressIsSimilar(array $dstMemberData, array $srcMemberData, bool $expected): void
    {
        $dstMember = new Member($dstMemberData);
        $srcMember = new Member($srcMemberData);
    
        $dst = $dstMember->address1;
        $src = $srcMember->address1;
    
        $merger = new AddressMerger($dst, $src, $dstMember, $srcMember);
        $method = new ReflectionMethod($merger, 'wholeAddressIsSimilar');
        $actual = $method->invoke($merger);
        
        self::assertEquals($expected, $actual);
    }
    
    public function provideWholeAddressIsSimilar(): array
    {
        $someAddress = self::getSomeAddress();
        unset($someAddress['postStatus']);
        
        return [
            'empty' => [[], [], true],
            'similar' => [$someAddress, $someAddress, true],
            'emptyAddress1' => [$someAddress, [...$someAddress, 'address1' => null], false],
            'emptyAddress2' => [$someAddress, [...$someAddress, 'address2' => null], false],
            'emptyZip' => [$someAddress, [...$someAddress, 'zip' => null], false],
            'emptyCity' => [$someAddress, [...$someAddress, 'city' => null], false],
            'emptyCountry' => [$someAddress, [...$someAddress, 'country' => null], true], // empty country = similar
            'differentAddress1' => [$someAddress, [...$someAddress, 'address1' => 'different'], false],
            'differentAddress2' => [$someAddress, [...$someAddress, 'address2' => 'different'], false],
            'differentZip' => [$someAddress, [...$someAddress, 'zip' => 'different'], false],
            'differentCity' => [$someAddress, [...$someAddress, 'city' => 'different'], false],
            'differentCountry' => [$someAddress, [...$someAddress, 'country' => 'other'], false],
        ];
    }
    
    private static function getSomeAddress(): array {
        return [
            'address1' => 'Am Damm 1',
            'address2' => 'Postfach 123',
            'zip' => '1234',
            'city' => 'Hinterpupfigen',
            'country' => 'CH',
            'postStatus' => 'active',
        ];
    }
}
