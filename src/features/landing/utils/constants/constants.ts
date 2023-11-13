import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';

export const REGISTRATION_SECTION_ID = 'registration-section';

export const CARD_ITEMS: IWhyWeCardItem[] = [
  {
    id: 'card-item-1',
    imageSrc: '/assets/img/WhyWeSection/1_code.png',
    text: 'Thanks to the open source code, you can modify and add CRM functions as you need them',
    title: 'Open source',
  },
  {
    id: 'card-item-2',
    imageSrc: '/assets/img/WhyWeSection/2_settings.png',
    text: 'Configure the system in a few clicks without programming knowledge and receive orders from your website in a few minutes',
    title: 'Easy to set up',
  },
  {
    id: 'card-item-3',
    imageSrc: '/assets/img/WhyWeSection/3_templates.png',
    text: 'You have: an online store, courses or a web studio\n' +
      'We have: special templates that will save you time',
    title: 'Ready templates',
  },
  {
    id: 'card-item-4',
    imageSrc: '/assets/img/WhyWeSection/4_services.png',
    text: 'We know the specific needs of EdTech, agencies and service providers - that\'s why we created a CRM that\'s easy to use',
    title: 'Ideal for services',
  },
  {
    id: 'card-item-5',
    imageSrc: '/assets/img/WhyWeSection/5_integrations.png',
    text: 'Connect your CMS and IP telephony in a few clicks.\n' +
      'And for specific integrations, use Zapier, APIs, and public libraries',
    title: 'All required integrations',
  },
  {
    id: 'card-item-6',
    imageSrc: '/assets/img/WhyWeSection/6_migration.png',
    text: 'Switch to Vilna in a few clicks with ready-made migration scripts from amoCRM and HubSpot',
    title: 'Bonus: easy migration',
  },
];
