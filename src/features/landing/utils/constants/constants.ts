import ISocialLink from '../../types/social/types';
import IUnlimitedIntegrationsItem from '../../types/unlimited-integrations/types';
import IWhyWeCardItem from '../../types/why-we/types';

export const SIGN_UP_SECTION_ID = 'SIGN_UP_SECTION_ID';

export const WHY_WE_CARD_ITEMS: IWhyWeCardItem[] = [
  {
    id: 'card-item-1',
    imageSrc: '/assets/img/WhyWeSection/1_code.png',
    title: 'why_we.headers.header_1',
    text: 'why_we.texts.text_1',
  },
  {
    id: 'card-item-2',
    imageSrc: '/assets/img/WhyWeSection/2_settings.png',
    title: 'why_we.headers.header_2',
    text: 'why_we.texts.text_2',
  },
  {
    id: 'card-item-3',
    imageSrc: '/assets/img/WhyWeSection/3_templates.png',
    title: 'why_we.headers.header_3',
    text: 'why_we.texts.text_3',
  },
  {
    id: 'card-item-4',
    imageSrc: '/assets/img/WhyWeSection/4_services.png',
    title: 'why_we.headers.header_4',
    text: 'why_we.texts.text_4',
  },
  {
    id: 'card-item-5',
    imageSrc: '/assets/img/WhyWeSection/5_integrations.png',
    title: 'why_we.headers.header_5',
    text: 'why_we.texts.text_5',
  },
  {
    id: 'card-item-6',
    imageSrc: '/assets/img/WhyWeSection/6_migration.png',
    title: 'why_we.headers.header_6',
    text: 'why_we.texts.text_6',
  },
];

export const UNLIMITED_INTEGRATIONS_CARD_ITEMS: IUnlimitedIntegrationsItem[] = [
  {
    id: 'item_1',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_1.png',
    text: 'unlimited_possibilities.cards_texts.text_1',
    title: 'unlimited_possibilities.cards_headings.heading_1',
    imageTitle: 'unlimited_possibilities.card_image_titles.card_titles.title_1',
  },
  {
    id: 'item_2',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_2.png',
    text: 'unlimited_possibilities.cards_texts.text_2',
    title: 'unlimited_possibilities.cards_headings.heading_2',
    imageTitle: 'unlimited_possibilities.card_image_titles.card_titles.title_2',
  },
  {
    id: 'item_3',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_3.png',
    text: 'unlimited_possibilities.cards_texts.text_3',
    title: 'unlimited_possibilities.cards_headings.heading_3',
    imageTitle: 'unlimited_possibilities.card_image_titles.card_titles.title_3',
  },
  {
    id: 'item_4',
    imageSrc: '/assets/img/UnlimitedIntegrations/Ruby_4.png',
    text: 'unlimited_possibilities.cards_texts.text_4',
    title: 'unlimited_possibilities.cards_headings.heading_4',
    imageTitle: 'unlimited_possibilities.card_image_titles.title_4',
  },
];

export const SOCIAL_LINKS: ISocialLink[] = [
  {
    id: 'google-link',
    icon: '/assets/img/SocialMedia/Icons/Google.png',
    title: 'Google',
    linkHref: '/',
  },
  {
    id: 'facebook-link',
    icon: '/assets/img/SocialMedia/Icons/Facebook.png',
    title: 'Facebook',
    linkHref: '/',
  },
  {
    id: 'github-link',
    icon: '/assets/img/SocialMedia/Icons/Github.png',
    title: 'GitHub',
    linkHref: '/',
  },
  {
    id: 'twitter-link',
    icon: '/assets/img/SocialMedia/Icons/Twitter.png',
    title: 'Twitter',
    linkHref: '/',
  },
];
