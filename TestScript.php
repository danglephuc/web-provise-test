<?php

class Travel
{
    private string $id;
    private DateTime $createdAt;
    private string $employeeName;
    private string $departure;
    private string $destination;
    private float $price;
    private string $companyId;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCreateAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreateAt(DateTime $createAt): self
    {
        $this->createdAt = $createAt;
        return $this;
    }

    public function getEmployeeName(): string
    {
        return $this->employeeName;
    }

    public function setEmployeeName(string $employeeName): self
    {
        $this->employeeName = $employeeName;
        return $this;
    }

    public function getDeparture(): string
    {
        return $this->departure;
    }

    public function setDeparture(string $departure): self
    {
        $this->departure = $departure;
        return $this;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    public function setCompanyId(string $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     * @param array $data
     * @return Travel
     * @throws Exception
     */
    public static function deserialize(array $data)
    {
        $travel = new Travel();
        $travel->setId($data['id']);
        $travel->setCreateAt(new DateTime($data['createdAt']));
        $travel->setEmployeeName($data['employeeName']);
        $travel->setDeparture($data['departure']);
        $travel->setDestination($data['destination']);
        $travel->setPrice((float)$data['price'] ?? 0);
        $travel->setCompanyId($data['companyId'] ?? "");

        return $travel;
    }
}

class Company
{
    private string $id;
    private DateTime $createdAt;
    private string $name;
    private string $parentId;

    private array $children;
    private array $travels;

    public function __construct()
    {
        $this->children = [];
        $this->travels = [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCreateAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreateAt(DateTime $createAt): self
    {
        $this->createdAt = $createAt;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getParentId(): string
    {
        return $this->parentId;
    }

    public function setParentId(string $parentId): self
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getTravels(): array
    {
        return $this->travels;
    }

    public function setTravels(array $travels):self
    {
        $this->travels = $travels;
        return $this;
    }

    public function getChildren() {
        return $this->children;
    }

    public function setChildren(array $children): self
    {
        $this->children = $children;
        return $this;
    }

    public function getTravelCost()
    {
        $cost = 0;
        /** @var Travel $travel */
        foreach ($this->travels as $travel) {
            $cost += $travel->getPrice();
        }
        /** @var Company $company */
        foreach ($this->children as $company) {
            $cost += $company->getTravelCost();
        }
        return $cost;
    }

    public function serialize(): array
    {
        $children = [];
        /** @var Company $company */
        foreach ($this->children as $company) {
            $children[] = $company->serialize();
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cost' => $this->getTravelCost(),
            'children' => $children,
        ];
    }

    /**
     * @param array $data
     * @return Company
     * @throws Exception
     */
    public static function deserialize(array $data): Company
    {
        $company = new Company();
        $company->setId($data['id']);
        $company->setCreateAt(new DateTime($data['createdAt']));
        $company->setName($data['name']);
        $company->setParentId($data['parentId'] ?? "0");
        return $company;
    }
}

class TestScript
{
    /**
     * @param $list
     * @param $parent
     * @return array
     */
    private function createTree(&$list, $parent)
    {
        $tree = array();
        /**
         * @var string $parentId
         * @var Company $company
         */
        foreach ($parent as $parentId => $company) {
            $companyId = $company->getId();
            if (isset($list[$companyId])) {
                $company->setChildren(self::createTree($list, $list[$companyId]));
            }
            $tree[] = $company;
        }
        return $tree;
    }

    /**
     * @param array $companies
     * @return array
     */
    private function createCompanyTree(array $companies)
    {
        $parentIdMap = [];
        /** @var Company $company */
        foreach ($companies as $company) {
            $parentIdMap[$company->getParentId()][] = $company;
        }

        return self::createTree($parentIdMap, $parentIdMap[0]);
    }

    /**
     * @param array $companiesData
     * @param array $travelsData
     * @return array
     * @throws Exception
     */
    private function getTravelCost(array $companiesData, array $travelsData)
    {
        $companyTravelMap = [];
        foreach ($travelsData as $travelData) {
            $travel = Travel::deserialize($travelData);
            $companyId = $travel->getCompanyId();
            if (!isset($companyTravelMap[$companyId])) {
                $companyTravelMap[$companyId] = [];
            }
            $companyTravelMap[$companyId][] = $travel;
        }
        $companies = [];
        foreach ($companiesData as $companyData) {
            $company = Company::deserialize($companyData);
            $travels = $companyTravelMap[$company->getId()] ?? [];
            $company->setTravels($travels);
            $companies[] = $company;
        }

        $companyTree = $this->createCompanyTree($companies);

        $result = [];
        /** @var Company $company */
        foreach ($companyTree as $company) {
            $result[] = $company->serialize();
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $start = microtime(true);

        $companyData = file_get_contents('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies');
        $companyData = json_decode($companyData, true);
        $travelData = file_get_contents('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels');
        $travelData = json_decode($travelData, true);

        $result = $this->getTravelCost($companyData, $travelData);
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;

        echo 'Total time: ' . (microtime(true) - $start);
    }
}
(new TestScript())->execute();
