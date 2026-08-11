<?php

namespace App\Form;

use App\Entity\ProductImage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class ProductImageType extends AbstractType
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/images/products')]
        private readonly string $uploadDir,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Upload real: fișierul ales e mutat în public/images/products/ și
            // numele generat e salvat în `filename` (vezi listener-ul de mai jos).
            ->add('imageFile', FileType::class, [
                'label' => 'Încarcă imagine',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image(maxSize: '5M', mimeTypesMessage: 'Încarcă un fișier imagine valid (jpg, png, webp).'),
                ],
            ])
            // Rămâne pentru retrocompatibilitate (poți referi un fișier deja
            // existent) și ca să vezi numele curent la editare. Opțional: dacă
            // încarci un fișier nou, se completează automat.
            ->add('filename', TextType::class, [
                'label' => 'Nume fișier',
                'required' => false,
                'help' => 'Se completează automat la încărcarea unei imagini. Lasă gol dacă încarci un fișier.',
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Ordine',
                'required' => false,
                'empty_data' => '0',
            ])
        ;

        // Procesăm fișierul per intrare de colecție (fiecare ProductImage are
        // propriul sub-formular, deci propriul upload).
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $image = $event->getData();
            if (!$image instanceof ProductImage) {
                return;
            }

            $uploaded = $form->get('imageFile')->getData();
            if ($uploaded instanceof UploadedFile) {
                $newName = bin2hex(random_bytes(8)).'.'.($uploaded->guessExtension() ?: 'bin');
                $uploaded->move($this->uploadDir, $newName);
                $image->setFilename($newName);
            }

            // O imagine fără fișier încărcat și fără nume e invalidă.
            if (!$image->getFilename()) {
                $form->addError(new \Symfony\Component\Form\FormError('Încarcă o imagine sau specifică numele fișierului.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductImage::class,
        ]);
    }
}
