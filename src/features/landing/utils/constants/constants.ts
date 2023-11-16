import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';
import { IUnlimitedIntegrationsItem } from '@/features/landing/types/unlimited-integrations/types';
import { ISocialLink } from '@/features/landing/types/social/types';

export const SIGN_UP_SECTION_ID = 'SIGN_UP_SECTION_ID';

export const WHY_WE_CARD_ITEMS: IWhyWeCardItem[] = [
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
    text:
      'You have: an online store, courses or a web studio\n' +
      'We have: special templates that will save you time',
    title: 'Ready templates',
  },
  {
    id: 'card-item-4',
    imageSrc: '/assets/img/WhyWeSection/4_services.png',
    text: "We know the specific needs of EdTech, agencies and service providers - that's why we created a CRM that's easy to use",
    title: 'Ideal for services',
  },
  {
    id: 'card-item-5',
    imageSrc: '/assets/img/WhyWeSection/5_integrations.png',
    text:
      'Connect your CMS and IP telephony in a few clicks.\n' +
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

export const UNLIMITED_INTEGRATIONS_CARD_ITEMS: IUnlimitedIntegrationsItem[] = [
  {
    id: 'item_1',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_1.png',
    text: "In case you didn't find it\n" + 'ready integration is required',
    title: 'Public API',
    imageTitle: 'Ruby Image 1',
  },
  {
    id: 'item_2',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_2.png',
    text: 'Integrate <a href="/" target="_blank">familiar services</a> in a few clicks',
    title: 'Ready plugins for CMS',
    imageTitle: 'Ruby Image 2',
  },
  {
    id: 'item_3',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_3.png',
    text: 'Get data about any events in CRM and automate complex business processes',
    title: 'Web hook system',
    imageTitle: 'Ruby Image 3',
  },
  {
    id: 'item_4',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_4.png',
    text: 'For custom integrations\n' + 'with specific products',
    title: 'Public libraries',
    imageTitle: 'Ruby Image 4',
  },
];

export const SOCIAL_LINKS: ISocialLink[] = [
  { id: 'google-link', icon: '/assets/img/SocialMedia/Icons/Google.png', title: 'Google', linkHref: '/' },
  { id: 'facebook-link', icon: '/assets/img/SocialMedia/Icons/Facebook.png', title: 'Facebook', linkHref: '/' },
  { id: 'github-link', icon: '/assets/img/SocialMedia/Icons/Github.png', title: 'GitHub', linkHref: '/' },
  { id: 'twitter-link', icon: '/assets/img/SocialMedia/Icons/Twitter.png', title: 'Twitter', linkHref: '/' },

];
