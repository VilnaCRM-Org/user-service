import { render } from '@testing-library/react';
import React from 'react';

import { PrivacyPolicy } from '@/components/UiFooter/PrivacyPolicy';

const privacyPolicyText: string = 'Privacy policy';
const usagePolicyText: string = 'Usage policy';

describe('PrivacyPolicy', () => {
  test('renders privacy and usage policy links', () => {
    const { getByText } = render(<PrivacyPolicy />);
    const privacyLink: HTMLElement = getByText(privacyPolicyText);
    const usagePolicyLink: HTMLElement = getByText(usagePolicyText);
    expect(privacyLink).toBeInTheDocument();
    expect(usagePolicyLink).toBeInTheDocument();
  });

  test('privacy link points to correct URL', () => {
    const { getByText } = render(<PrivacyPolicy />);
    const privacyLink: HTMLElement = getByText(privacyPolicyText);
    expect(privacyLink).toBeInTheDocument();
  });

  test('usage policy link points to correct URL', () => {
    const { getByText } = render(<PrivacyPolicy />);
    const usagePolicyLink: HTMLElement = getByText(usagePolicyText);
    expect(usagePolicyLink).toBeInTheDocument();
  });
});
