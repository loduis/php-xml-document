<?php

namespace XML\Tests {

    use Invoice;

    use XML\Support\Single;

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
                    'other_signs' => 'edificio torre & XYZ'
                ],
                'phone' =>[
                    'code' => '506',
                    'number' => '22122000'
                ],
                'email' => 'facturaelectronica@test.com',
            ];
            $invoice->seller = new Single('Prueba', [
                'firstName' => 'Otra',
                'secondName' => null, // no se muestra el attributo
                'lastName' => ''
            ]);
            $source1  = $invoice->pretty();
            $source2 = $invoice->_pretty();
            $this->assertEquals($source1, $source2);
            $this->assertMatchesXmlSnapshot($source1);
        }
    }
}

namespace {

    use XML\Document;
    use XML\Document\Creator;
    use Contact\Document as ContactDocument;
    use XML\Document\Element;
    use XML\Support\Single;

    class Invoice extends Document {

        protected $fillable = [
            'key' => 'string',
            'number' => 'string',
            'date' => 'string',
            'sender' => Contact::class,
            'receiver' => Contact::class,
            'seller' => Single::class
        ];

        public function toArray(): array
        {
            return [
                'Clave' => $this->key,
                'NumeroConsecutivo' => $this->number,
                'FechaEmision' => $this->date,
                'Emisor' => $this->sender,
                'Receptor' => $this->receiver,
                'Vendedor' => $this->seller
            ];
        }

        protected function getName()
        {
            return 'FacturaElectronica';
        }

        protected function creator()
        {
            return new Creator($this, [
                'xmlns' => 'https://tribunet.hacienda.go.cr/docs/esquemas/2017/v4.2/facturaElectronica'
            ]);
        }

        public function _pretty()
        {
            return $this->creator()->toDocument(true)->saveXML();
        }
    }


    class Contact extends Element
    {
        protected $fillable = [
            'name' => 'string',
            'document' => ContactDocument::class,
            'tradename' => 'string',
            'address' => Address::class,
            'email' => 'string',
            'phone' => Phone::class,
            'fax' => Phone::class
        ];

        public function toArray(): array
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

    class Address extends Element
    {
        protected $fillable = [
            'province' => 'string',
            'canton' => 'string',
            'district' => 'string',
            'neighborhood' => 'string',
            'otherSigns' => 'string'
        ];

        public function toArray(): array
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


    class Phone extends Element
    {
        protected $fillable = [
            'code' => 'string',
            'number' => 'string'
        ];

        public function toArray(): array
        {
            return [
                'CodigoPais' => $this->code,
                'NumTelefono' => $this->number
            ];
        }
    }
}

namespace Contact {

    use XML\Document\Element;

    class Document extends Element
    {
        protected $fillable = [
            'type' => 'string',
            'number' => 'string'
        ];

        public function toArray(): array
        {
            return [
                'Tipo' => $this->type,
                'Numero' => $this->number
            ];
        }
    }
}
