import FacebookFooterIcon from '../../assets/svg/social-icons/facebook.svg';
import GitHubFooterIcon from '../../assets/svg/social-icons/github.svg';
import InstagramFooterIcon from '../../assets/svg/social-icons/instagram.svg';
import LinkedinFooterIcon from '../../assets/svg/social-icons/linked-in.svg';
import { SocialMedia } from '../../types/social-media';

export const socialLinks: SocialMedia[] = [
  {
    id: 'Instagram-link',
    icon: InstagramFooterIcon,
    alt: 'footer.alt_images.instagram',
    linkHref: '/',
    ariaLabel: 'footer.aria_labels.instagram',
  },
  {
    id: 'GitHub-link',
    icon: GitHubFooterIcon,
    alt: 'footer.alt_images.github',
    linkHref: '/',
    ariaLabel: 'footer.aria_labels.github',
  },
  {
    id: 'Facebook-link',
    icon: FacebookFooterIcon,
    alt: 'footer.alt_images.facebook',
    linkHref: '/',
    ariaLabel: 'footer.aria_labels.facebook',
  },
  {
    id: 'Linkedin-link',
    icon: LinkedinFooterIcon,
    alt: 'footer.alt_images.linkedin',
    linkHref: '/',
    ariaLabel: 'footer.aria_labels.linkedin',
  },
];
