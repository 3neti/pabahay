<?php

namespace LBHurtado\Mortgage\Data\Inputs;

use LBHurtado\Mortgage\Contracts\BuyerInterface;
use LBHurtado\Mortgage\Contracts\OrderInterface;
use LBHurtado\Mortgage\Contracts\PropertyInterface;

class MortgageParticulars
{
    public function __construct(
        public BuyerInterface $buyer,
        public PropertyInterface $property,
        public OrderInterface $order,
    ) {}

    public static function fromBooking(BuyerInterface $buyer, PropertyInterface $property, OrderInterface $order): static
    {
        return new static (
            buyer: $buyer,
            property: $property,
            order: $order,
        );
    }

    public function buyer(): BuyerInterface
    {
        return $this->buyer;
    }

    public function property(): PropertyInterface
    {
        return $this->property;
    }

    public function order(): OrderInterface
    {
        return $this->order;
    }

    public function toArray(): array
    {
        return [
            'buyer' => [
                'age' => $this->buyer->getAge(),
                'monthly_gross_income' => $this->buyer->getMonthlyGrossIncome()->getAmount()->toFloat(),
            ],
            'property' => [
                'total_contract_price' => $this->property->getTotalContractPrice()->getAmount()->toFloat(),
                'lending_institution' => $this->property->getLendingInstitution()->key(),
            ],
            'order' => [
                'percent_down_payment' => $this->order->getPercentDownPayment()?->value(),
            ],
        ];
    }
}
