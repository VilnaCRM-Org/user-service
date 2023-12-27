import FaceBookIcon from '../../assets/img/SocialMedia/Icons/Facebook.png';
import GitHubIcon from '../../assets/img/SocialMedia/Icons/Github.png';
import GoogleIcon from '../../assets/img/SocialMedia/Icons/Google.png';
import TwitterIcon from '../../assets/img/SocialMedia/Icons/Twitter.png';
import UltimatedRuby1Icon from '../../assets/img/UnlimitedIntegrations/Ruby_1.png';
import UltimatedRuby2Icon from '../../assets/img/UnlimitedIntegrations/Ruby_2.png';
import UltimatedRuby3Icon from '../../assets/img/UnlimitedIntegrations/Ruby_3.png';
import UltimatedRuby4Icon from '../../assets/img/UnlimitedIntegrations/Ruby_4.png';
import WhyUsCodeIcon from '../../assets/img/WhyWeSection/1_code.png';
import WhyUsSettingsIcon from '../../assets/img/WhyWeSection/2_settings.png';
import WhyUsTemplatesIcon from '../../assets/img/WhyWeSection/3_templates.png';
import WhyUsServicesIcon from '../../assets/img/WhyWeSection/4_services.png';
import WhyUsIntegrationsIcon from '../../assets/img/WhyWeSection/5_integrations.png';
import WhyUsMigrationIcon from '../../assets/img/WhyWeSection/6_migration.png';
import GoogleDrawerIcon from '../../assets/svg/header-drawer/socials/facebook.svg';
import GitHubDrawerIcon from '../../assets/svg/header-drawer/socials/github.svg';
import FaceBookDrawerIcon from '../../assets/svg/header-drawer/socials/instagram.svg';
import TwitterDrawerIcon from '../../assets/svg/header-drawer/socials/linked-in.svg';
import GoogleFooterIcon from '../../assets/svg/social-icons/facebook.svg';
import GitHubFooterIcon from '../../assets/svg/social-icons/github.svg';
import FaceBookFooterIcon from '../../assets/svg/social-icons/instagram.svg';
import TwitterFooterIcon from '../../assets/svg/social-icons/linked-in.svg';
import ISocialLink from '../../types/social/types';
import IUnlimitedIntegrationsItem from '../../types/unlimited-integrations/types';
import IWhyWeCardItem from '../../types/why-we/types';

console.log(FaceBookIcon);

export const SIGN_UP_SECTION_ID = 'SIGN_UP_SECTION_ID';

export const WHY_WE_CARD_ITEMS: IWhyWeCardItem[] = [
  {
    id: 'card-item-1',
    imageSrc: WhyUsCodeIcon,
    title: 'why_we.headers.header_open_source',
    text: 'why_we.texts.text_open_source',
  },
  {
    id: 'card-item-2',
    imageSrc: WhyUsSettingsIcon,
    title: 'why_we.headers.header_ease_of_setup',
    text: 'why_we.texts.text_configure_system',
  },
  {
    id: 'card-item-3',
    imageSrc: WhyUsTemplatesIcon,
    title: 'why_we.headers.header_ready_templates',
    text: 'why_we.texts.text_you_have_store',
  },
  {
    id: 'card-item-4',
    imageSrc: WhyUsServicesIcon,
    title: 'why_we.headers.header_ideal_for_services',
    text: 'why_we.texts.text_we_know_specific_needs',
  },
  {
    id: 'card-item-5',
    imageSrc: WhyUsIntegrationsIcon,
    title: 'why_we.headers.header_all_required_integrations',
    text: 'why_we.texts.text_connect_your_cms',
  },
  {
    id: 'card-item-6',
    imageSrc: WhyUsMigrationIcon,
    title: 'why_we.headers.header_bonus',
    text: 'why_we.texts.text_switch_to_vilna',
  },
];

export const UNLIMITED_INTEGRATIONS_CARD_ITEMS: IUnlimitedIntegrationsItem[] = [
  {
    id: 'item_1',
    imageSrc: UltimatedRuby1Icon,
    width: 77,
    height: 65,
    text: 'unlimited_possibilities.cards_texts.text_1',
    title: 'unlimited_possibilities.cards_headings.heading_public_api',
    imageTitle: 'unlimited_possibilities.card_image_titles.title_for_first',
  },
  {
    id: 'item_2',
    width: 44,
    height: 75,
    imageSrc: UltimatedRuby2Icon,
    text: 'unlimited_possibilities.cards_texts.text_2',
    title: 'unlimited_possibilities.cards_headings.heading_ready_plugins',
    imageTitle: 'unlimited_possibilities.card_image_titles.title_for_second',
  },
  {
    id: 'item_3',
    imageSrc: UltimatedRuby3Icon,
    width: 50,
    height: 80,
    text: 'unlimited_possibilities.cards_texts.text_3',
    title: 'unlimited_possibilities.cards_headings.heading_system',
    imageTitle: 'unlimited_possibilities.card_image_titles.title_for_third',
  },
  {
    id: 'item_4',
    imageSrc: UltimatedRuby4Icon,
    width: 75,
    height: 67,
    text: 'unlimited_possibilities.cards_texts.text_4',
    title: 'unlimited_possibilities.cards_headings.heading_libraries',
    imageTitle: 'unlimited_possibilities.card_image_titles.title_for_fourth',
  },
];

export const SOCIAL_LINKS: ISocialLink[] = [
  {
    id: 'google-link',
    icon: GoogleIcon,
    title: 'Google',
    linkHref: '/',
  },
  {
    id: 'facebook-link',
    icon: FaceBookIcon,
    title: 'Facebook',
    linkHref: '/',
  },
  {
    id: 'github-link',
    icon: GitHubIcon,
    title: 'GitHub',
    linkHref: '/',
  },
  {
    id: 'twitter-link',
    icon: TwitterIcon,
    title: 'Twitter',
    linkHref: '/',
  },
];
export const FOOTER_SOCIAL_LINKS: ISocialLink[] = [
  {
    id: 'google-link',
    icon: FaceBookFooterIcon,
    title: 'Instagram',
    linkHref: '/',
  },
  {
    id: 'facebook-link',
    icon: GitHubFooterIcon,
    title: 'GitHub',
    linkHref: '/',
  },
  {
    id: 'github-link',
    icon: GoogleFooterIcon,
    title: 'Facebook',
    linkHref: '/',
  },
  {
    id: 'twitter-link',
    icon: TwitterFooterIcon,
    title: 'Linkedin',
    linkHref: '/',
  },
];
export const DRAWER_SOCIAL_LINKS: ISocialLink[] = [
  {
    id: 'google-link',
    icon: FaceBookDrawerIcon,
    title: 'Instagram',
    linkHref: '/',
  },
  {
    id: 'facebook-link',
    icon: GitHubDrawerIcon,
    title: 'GitHub',
    linkHref: '/',
  },
  {
    id: 'github-link',
    icon: GoogleDrawerIcon,
    title: 'Facebook',
    linkHref: '/',
  },
  {
    id: 'twitter-link',
    icon: TwitterDrawerIcon,
    title: 'Linkedin',
    linkHref: '/',
  },
];
