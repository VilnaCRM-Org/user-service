import FacebookDrawerIcon from '../../assets/svg/social-icons/facebook.svg';
import GitHubDrawerIcon from '../../assets/svg/social-icons/github.svg';
import InstagramDrawerIcon from '../../assets/svg/social-icons/instagram.svg';
import LinkedinDrawerIcon from '../../assets/svg/social-icons/linked-in.svg';

export const links = [
  { id: 'Advantages', link: '#Advantages', value: 'header.advantages' },
  {
    id: 'forWhoSectionStyles',
    link: '#forWhoSectionStyles',
    value: 'header.for_who',
  },
  { id: 'Integration', link: '#Integration', value: 'header.integration' },
  { id: 'Contacts', link: '#Contacts', value: 'header.contacts' },
];
export const navList = [
  {
    id: 'advantages',
    title: 'header.advantages',
    link: '#Advantages',
  },
  {
    id: 'for-who',
    title: 'header.for_who',
    link: '#forWhoSectionStyles',
  },
  {
    id: 'integration',
    title: 'header.integration',
    link: '#Integration',
  },
  {
    id: 'contacts',
    title: 'header.contacts',
    link: '#Contacts',
  },
];

export const socialMedia = [
  {
    id: 'instagram-link',
    icon: InstagramDrawerIcon,
    alt: 'header.drawer.alt_social_images.instagram',
    ariaLabel: 'header.drawer.aria_labels_social_images.instagram',
    linkHref: '/',
  },
  {
    id: 'gitHub-link',
    icon: GitHubDrawerIcon,
    alt: 'header.drawer.alt_social_images.github',
    ariaLabel: 'header.drawer.aria_labels_social_images.github',
    linkHref: '/',
  },
  {
    id: 'facebook-link',
    icon: FacebookDrawerIcon,
    alt: 'header.drawer.alt_social_images.facebook',
    ariaLabel: 'header.drawer.aria_labels_social_images.facebook',
    linkHref: '/',
  },
  {
    id: 'linkedin-link',
    icon: LinkedinDrawerIcon,
    alt: 'header.drawer.alt_social_images.linkedin',
    ariaLabel: 'header.drawer.aria_labels_social_images.linkedin',
    linkHref: '/',
  },
];
