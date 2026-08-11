<?php

namespace App\Form;

use App\Entity\ProductImage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductImageType extends AbstractType
{
    private const array ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const int MAX_BYTES = 5 * 1024 * 1024;

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
            // NB: fără constrângere declarativă Image aici — ea ar rula la
            // validare, DUPĂ ce listener-ul mută fișierul, și n-ar mai găsi
            // fișierul (mutat) → 422. Validăm manual, înainte de mutare.
            ->add('imageFile', FileType::class, [
                'label' => 'Încarcă imagine',
                'mapped' => false,
                'required' => false,
                'help' => 'jpg, png, webp sau gif, max 5 MB.',
            ])
            // Rămâne pentru retrocompatibilitate (poți referi un fișier deja
            // existent) și ca să vezi numele curent la editare. Se completează
            // automat dacă încarci un fișier.
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
                // Validare manuală ÎNAINTE de mutare (fișierul temporar există acum).
                if (!$uploaded->isValid()) {
                    $form->get('imageFile')->addError(new FormError('Încărcarea fișierului a eșuat. Încearcă din nou.'));

                    return;
                }
                if ($uploaded->getSize() > self::MAX_BYTES) {
                    $form->get('imageFile')->addError(new FormError('Fișierul e prea mare (max 5 MB).'));

                    return;
                }
                if (!\in_array($uploaded->getMimeType(), self::ALLOWED_MIME, true)) {
                    $form->get('imageFile')->addError(new FormError('Încarcă un fișier imagine valid (jpg, png, webp, gif).'));

                    return;
                }

                $newName = bin2hex(random_bytes(8)).'.'.($uploaded->guessExtension() ?: 'jpg');
                $uploaded->move($this->uploadDir, $newName);
                $image->setFilename($newName);
            }

            // O imagine fără fișier încărcat și fără nume e invalidă.
            if (!$image->getFilename()) {
                $form->addError(new FormError('Încarcă o imagine sau specifică numele fișierului.'));
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
