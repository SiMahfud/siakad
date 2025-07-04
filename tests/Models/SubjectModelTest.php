<?php

namespace Tests\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\SubjectModel;

class SubjectModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected $subjectModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subjectModel = new SubjectModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->subjectModel);
    }

    public function testCreateSubjectWithValidData()
    {
        $data = [
            'subject_name' => 'Matematika Wajib',
            'subject_code' => 'MTK-WAJIB-X',
            'is_pilihan'   => 0,
        ];
        $subjectId = $this->subjectModel->insert($data);

        $this->assertIsNumeric($subjectId, "Insert should return the new subject ID. Errors: ".implode(', ', $this->subjectModel->errors()));
        $insertedSubject = $this->subjectModel->find($subjectId);
        $this->assertNotNull($insertedSubject);
        $this->assertEquals($data['subject_name'], $insertedSubject['subject_name']);
        $this->assertEquals($data['subject_code'], $insertedSubject['subject_code']);
        $this->assertEquals($data['is_pilihan'], $insertedSubject['is_pilihan']);
    }

    public function testCreateSubjectWithMinimalValidData()
    {
        $data = [
            'subject_name' => 'Bahasa Indonesia',
            'is_pilihan'   => 0, // Explicitly provide to pass model validation for in_list[0,1]
        ];
        $subjectId = $this->subjectModel->insert($data);

        $this->assertIsNumeric($subjectId, "Insert should return ID. Errors: ".implode(', ', $this->subjectModel->errors()));
        $insertedSubject = $this->subjectModel->find($subjectId);
        $this->assertNotNull($insertedSubject);
        $this->assertEquals('Bahasa Indonesia', $insertedSubject['subject_name']);
        $this->assertEquals(0, $insertedSubject['is_pilihan']);
        $this->assertNull($insertedSubject['subject_code']);
    }

    public function testCreateSubjectFailsIfNameMissing()
    {
        $data = [
            'subject_code' => 'BIO-X',
            'is_pilihan'   => 1,
        ];
        $result = $this->subjectModel->insert($data);
        $errors = $this->subjectModel->errors();

        $this->assertFalse($result, "Insert should fail if subject_name is missing.");
        $this->assertArrayHasKey('subject_name', $errors);
        $this->assertMatchesRegularExpression('/required/i', $errors['subject_name']);
    }

    public function testCreateSubjectFailsIfNameTooShort()
    {
        $data = [
            'subject_name' => 'MT',
            'subject_code' => 'MT-XI',
            'is_pilihan'   => 0, // Add to ensure this isn't the cause of failure
        ];
        $result = $this->subjectModel->insert($data);
        $errors = $this->subjectModel->errors();

        $this->assertFalse($result, "Insert should fail if subject_name is too short.");
        $this->assertArrayHasKey('subject_name', $errors);
        $this->assertStringContainsStringIgnoringCase('at least 3 characters', $errors['subject_name']);
    }

    public function testCreateSubjectFailsIfCodeTaken()
    {
        $firstSubject = [
            'subject_name' => 'Fisika Dasar',
            'subject_code' => 'FIS-UNIQUE-101',
            'is_pilihan'   => 0,
        ];
        $this->subjectModel->insert($firstSubject);

        $duplicateData = [
            'subject_name' => 'Kimia Dasar',
            'subject_code' => 'FIS-UNIQUE-101',
            'is_pilihan'   => 0,
        ];
        $result = $this->subjectModel->insert($duplicateData);
        $errors = $this->subjectModel->errors();

        $this->assertFalse($result, "Insert should fail if subject_code is already taken.");
        $this->assertArrayHasKey('subject_code', $errors);
        $this->assertStringContainsStringIgnoringCase('must contain a unique value', $errors['subject_code']);
    }

    public function testCreateSubjectSucceedsIfCodeIsEmptyAndAnotherIsEmpty()
    {
        $subject1_id = $this->subjectModel->insert([
            'subject_name' => 'Sejarah Indonesia',
            'subject_code' => null,
            'is_pilihan'   => 0,
        ]);
        $this->assertIsNumeric($subject1_id, "First subject with null code should insert. Errors: ".implode(', ', $this->subjectModel->errors()));

        $subject2_id = $this->subjectModel->insert([
            'subject_name' => 'Pendidikan Pancasila',
            'subject_code' => null,
            'is_pilihan'   => 0,
        ]);
        $this->assertIsNumeric($subject2_id, "Second subject with null code should also insert. Errors: ".implode(', ', $this->subjectModel->errors()));
        $this->seeInDatabase('subjects', ['id' => $subject2_id, 'subject_name' => 'Pendidikan Pancasila']);
    }

    public function testUpdateSubject()
    {
        $subjectId = $this->subjectModel->insert([
            'subject_name' => 'Geografi Awal',
            'subject_code' => 'GEO-AWAL',
            'is_pilihan'   => 0,
        ]);
        $this->assertIsNumeric($subjectId);

        $updatedData = [
            'subject_name' => 'Geografi Lanjutan',
            'subject_code' => 'GEO-LANJUT',
            'is_pilihan'   => 1,
        ];
        $result = $this->subjectModel->update($subjectId, $updatedData);

        $this->assertTrue($result, "Update should be successful. Errors: ".implode(', ', $this->subjectModel->errors()));

        $dbSubject = $this->subjectModel->find($subjectId);
        $this->assertEquals($updatedData['subject_name'], $dbSubject['subject_name']);
        $this->assertEquals($updatedData['subject_code'], $dbSubject['subject_code']);
        $this->assertEquals($updatedData['is_pilihan'], $dbSubject['is_pilihan']);
    }

    public function testDeleteSubject()
    {
        $subjectId = $this->subjectModel->insert([
            'subject_name' => 'Ekonomi Untuk Dihapus',
            'subject_code' => 'EKO-DEL',
            'is_pilihan'   => 0, // Ensure valid insert
        ]);
        $this->assertIsNumeric($subjectId, "Subject for deletion should be inserted. Errors: ".implode(', ', $this->subjectModel->errors()));

        $result = $this->subjectModel->delete($subjectId);
        $this->assertTrue($result, "Delete should be successful.");
        $this->dontSeeInDatabase('subjects', ['id' => $subjectId]);
    }
}
