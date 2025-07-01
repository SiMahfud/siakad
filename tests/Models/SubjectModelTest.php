<?php

namespace Tests\Models;

use App\Models\SubjectModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class SubjectModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // No specific seeder needed for SubjectModel itself,
    // unless other tests depend on pre-existing subjects.
    // protected $seed        = 'SubjectSeeder';

    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new SubjectModel();
    }

    private function getValidSubjectData(array $override = []): array
    {
        return array_merge([
            'subject_name' => 'Test Subject ' . uniqid(),
            'subject_code' => 'TSUB' . uniqid(), // Must be unique
            'is_pilihan'   => '0', // Default to Wajib (Core) as string '0'
        ], $override);
    }

    public function testCreateSubjectSuccessfully()
    {
        $data = $this->getValidSubjectData();
        $subjectId = $this->model->insert($data);

        $this->assertIsNumeric($subjectId);
        $this->seeInDatabase('subjects', [
            'subject_code' => $data['subject_code'],
            'subject_name' => $data['subject_name'],
            'is_pilihan'   => (int)$data['is_pilihan'] // Cast boolean to int for DB check if needed
        ]);
    }

    public function testSubjectNameIsRequired()
    {
        $data = $this->getValidSubjectData(['subject_name' => '']);
        $this->assertFalse($this->model->insert($data));
        $this->assertArrayHasKey('subject_name', $this->model->errors());
    }

    public function testSubjectCodeIsUnique()
    {
        $commonCode = 'UNIQUE_CODE_SUB';
        $data1 = $this->getValidSubjectData(['subject_code' => $commonCode]);
        $this->model->insert($data1);

        $data2 = $this->getValidSubjectData(['subject_code' => $commonCode]);
        $this->assertFalse($this->model->insert($data2));
        $this->assertArrayHasKey('subject_code', $this->model->errors());
        $this->assertStringContainsStringIgnoringCase('is already registered', $this->model->errors()['subject_code']);
    }

    public function testSubjectCodeCanBeNull()
    {
        // subject_code uses permit_empty
        $data = $this->getValidSubjectData(['subject_code' => null]);
        $subjectId = $this->model->insert($data);
        $this->assertIsNumeric($subjectId, "Subject creation should succeed with null subject_code. Errors: " . print_r($this->model->errors(), true));
        $this->seeInDatabase('subjects', ['id' => $subjectId, 'subject_code' => null]);
    }

    public function testIsPilihanIsValid()
    {
        // is_pilihan is 'required|in_list[0,1]'
        $dataInvalid = $this->getValidSubjectData(['is_pilihan' => '2']);
        $this->assertFalse($this->model->insert($dataInvalid));
        $this->assertArrayHasKey('is_pilihan', $this->model->errors());

        $dataValidTrue = $this->getValidSubjectData(['is_pilihan' => '1']); // Use string '1'
        $subjectIdTrue = $this->model->insert($dataValidTrue);
        $this->assertIsNumeric($subjectIdTrue, "Failed to insert subject with is_pilihan='1'. Errors: ".print_r($this->model->errors(), true));
        $this->seeInDatabase('subjects', ['id' => $subjectIdTrue, 'is_pilihan' => 1]); // DB stores as INT

        $dataValidFalse = $this->getValidSubjectData(['is_pilihan' => '0']); // Use string '0'
        $subjectIdFalse = $this->model->insert($dataValidFalse);
        $this->assertIsNumeric($subjectIdFalse, "Failed to insert subject with is_pilihan='0'. Errors: ".print_r($this->model->errors(), true));
        $this->seeInDatabase('subjects', ['id' => $subjectIdFalse, 'is_pilihan' => 0]); // DB stores as INT
    }

    public function testUpdateSubject()
    {
        $data = $this->getValidSubjectData();
        $subjectId = $this->model->insert($data);

        $updatedData = [
            'subject_name' => 'Updated Subject Name',
            'subject_code' => 'UPDATED_SUB_' . uniqid(),
            'is_pilihan'   => true,
        ];
        $this->model->update($subjectId, $updatedData);
        $this->seeInDatabase('subjects', [
            'id' => $subjectId,
            'subject_name' => 'Updated Subject Name',
            'is_pilihan' => 1
        ]);
    }

    public function testDeleteSubject()
    {
        $data = $this->getValidSubjectData();
        $subjectId = $this->model->insert($data);
        $this->seeInDatabase('subjects', ['id' => $subjectId]);

        $this->model->delete($subjectId);
        $this->dontSeeInDatabase('subjects', ['id' => $subjectId]);
    }
}
