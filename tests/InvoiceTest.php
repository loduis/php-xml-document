<?php

namespace XML\Tests {

    use Invoice;
    use XML\Document\Creator;

    final class InvoiceTest extends TestCase
    {
        public function testShouldGenerateInvoice()
        {
            $invoice = new Invoice();
            $invoice->key = 'bdH9iHQLepxN22g17Oj/TzdRCQD/ZJb0H1HdGZpNDzzG5RdgGcyXqvJV1Q5ZGUlDSoQ=';

            $invoice->date = '2018-02-20';
            $invoice->sender = [
                'name' => 'Juan Perez',
                'document' => [
                    'type' => '02',
                    'number' =>'3101234567'
                ],
                'address' => [
                    'province' => '2',
                    'canton'  => '01',
                    'district' => '10',
                    'neighborhood' => '02',
                    'other_signs' => 'edificio torre XYZ'
                ],
                'phone' =>[
                    'code' => '506',
                    'number' => '22122000'
                ],
                'email' => 'facturaelectronica@test.com',
            ];

            $creator = new Creator($invoice, [
                'https://tribunet.hacienda.go.cr/docs/esquemas/2017/v4.2/facturaElectronica'
            ]);

            $this->assertMatchesXmlSnapshot((string) $creator->toDocument()->pretty());
        }
    }

}

namespace {

    use Contact\Document;
    use XML\Document\Object;
    use XML\Document\Contract;

    class Invoice extends Contract {

        protected $fillable = [
            'key' => 'string',
            'number' => 'string',
            'date' => 'string',
            'sender' => Contact::class,
            'receiver' => Contact::class
        ];

        public function toArray()
        {
            return [
                'Clave' => $this->key,
                'NumeroConsecutivo' => $this->number,
                'FechaEmision' => $this->date,
                'Emisor' => $this->sender,
                'Receptor' => $this->receiver
            ];
        }

        protected function getName()
        {
            return 'FacturaElectronica';
        }
    }


    class Contact extends Object
    {
        protected $fillable = [
            'name' => 'string',
            'document' => Document::class,
            'tradename' => 'string',
            'address' => Address::class,
            'email' => 'string',
            'phone' => Phone::class,
            'fax' => Phone::class
        ];

        public function toArray()
        {
            return [
                'Nombre' => $this->name,
                'Identificacion' => $this->document,
                'NombreComercial' => $this->tradename,
                'Ubicacion' => $this->address,
                'Telefono' => $this->phone,
                'Fax' => $this->fax,
                'CorreoElectronico' => $this->email
            ];
        }
    }

    class Address extends Object
    {
        protected $fillable = [
            'province' => 'string',
            'canton' => 'string',
            'district' => 'string',
            'neighborhood' => 'string',
            'otherSigns' => 'string'
        ];

        public function toArray()
        {
            return [
                'Provincia' => $this->province,
                'Canton' => $this->canton,
                'Distrito' => $this->district,
                'Barrio' => $this->neighborhood,
                'OtrasSenas' => $this->otherSigns
            ];
        }
    }


    class Phone extends Object
    {
        protected $fillable = [
            'code' => 'string',
            'number' => 'string'
        ];

        public function toArray()
        {
            return [
                'CodigoPais' => $this->code,
                'NumTelefono' => $this->number
            ];
        }
    }
}

namespace Contact {

    use XML\Document\Object;

    class Document extends Object
    {
        protected $fillable = [
            'type' => 'string',
            'number' => 'string'
        ];

        public function toArray()
        {
            return [
                'Tipo' => $this->type,
                'Numero' => $this->number
            ];
        }
    }
}

